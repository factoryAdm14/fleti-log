"use strict";

/**
 * Fleti zone map helpers — shared by zone index and edit views.
 */
const FletiZoneMap = {
    defaultCenter: { lat: -23.55052, lng: -46.633308 }, // São Paulo — fallback for Fleti (BR)

    polygonStyle: {
        editable: true,
        draggable: false,
        clickable: true,
        strokeColor: "#14b19e",
        strokeOpacity: 0.9,
        strokeWeight: 2,
        fillColor: "#14b19e",
        fillOpacity: 0.2,
    },

    autoGrow(elementId) {
        const element = document.getElementById(elementId || "coordinates");
        if (!element || element.type === "hidden") {
            return;
        }
        element.style.height = "5px";
        element.style.height = element.scrollHeight + "px";
    },

    formatPathToCoordinates(path, closeRing = true) {
        const points = path.getArray();
        if (!points || points.length < 3) {
            return "";
        }
        const formatLatLng = (latLng) =>
            `(${latLng.lat().toFixed(8)},${latLng.lng().toFixed(8)})`;
        let formatted = points.map(formatLatLng).join(",");

        if (closeRing && points.length >= 3) {
            const first = points[0];
            const last = points[points.length - 1];
            if (first.lat() !== last.lat() || first.lng() !== last.lng()) {
                formatted += `,${formatLatLng(first)}`;
            }
        }
        return formatted;
    },

    distanceMeters(a, b) {
        const R = 6371000;
        const dLat = ((b.lat() - a.lat()) * Math.PI) / 180;
        const dLng = ((b.lng() - a.lng()) * Math.PI) / 180;
        const lat1 = (a.lat() * Math.PI) / 180;
        const lat2 = (b.lat() * Math.PI) / 180;
        const h =
            Math.sin(dLat / 2) * Math.sin(dLat / 2) +
            Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
        return 2 * R * Math.asin(Math.sqrt(h));
    },

    countCoordinatePairs(value) {
        if (!value || typeof value !== "string") {
            return 0;
        }
        const matches = value.match(/\([^)]+\)/g);
        return matches ? matches.length : 0;
    },

    hasMinimumPoints(value, minimum = 3) {
        return this.countCoordinatePairs(value) >= minimum;
    },

    safeClearPolygon(polygon) {
        if (polygon && typeof polygon.setMap === "function") {
            try {
                polygon.setMap(null);
            } catch (error) {
                console.error("[FletiZoneMap] Failed to clear polygon:", error);
            }
        }
    },

    setCoordinatesFromOverlay(overlay) {
        if (!overlay || !overlay.getPath) {
            console.error("[FletiZoneMap] Invalid polygon overlay");
            return "";
        }
        const formatted = this.formatPathToCoordinates(overlay.getPath());
        this.setCoordinatesValue(formatted);
        return formatted;
    },

    setCoordinatesValue(value) {
        $("#coordinates").val(value || "");
        this.autoGrow("coordinates");
    },

    syncCoordinatesBeforeSubmit(drawer) {
        if (!drawer || typeof drawer.getPolygon !== "function") {
            return;
        }

        if (typeof drawer.isDrawing === "function" && drawer.isDrawing()) {
            if (typeof drawer.finishDrawing === "function") {
                drawer.finishDrawing();
            }
            return;
        }

        const polygon = drawer.getPolygon();
        if (polygon) {
            this.setCoordinatesFromOverlay(polygon);
        }
    },

    validateFormSubmit(event, minimumPoints = 3, drawer = null) {
        if (drawer) {
            this.syncCoordinatesBeforeSubmit(drawer);
        }

        const value = $("#coordinates").val();
        if (!value || value.trim() === "") {
            toastr.error(window.zoneMapMessages?.defineZone || "Please define zone on the map");
            event.preventDefault();
            return false;
        }
        if (!this.hasMinimumPoints(value, minimumPoints)) {
            toastr.error(
                window.zoneMapMessages?.minPoints ||
                    `Please draw a zone with at least ${minimumPoints} points`
            );
            event.preventDefault();
            return false;
        }
        return true;
    },

    setDrawStatus(messageKey, fallback) {
        const el = document.getElementById("fleti-zone-draw-status");
        if (!el) {
            return;
        }
        el.textContent =
            (messageKey && window.zoneMapMessages && window.zoneMapMessages[messageKey]) ||
            fallback ||
            "";
    },

    centerMapFromGeolocation(map) {
        if (!navigator.geolocation || !map) {
            map.setCenter(this.defaultCenter);
            return;
        }
        navigator.geolocation.getCurrentPosition(
            (position) => {
                map.setCenter({
                    lat: position.coords.latitude,
                    lng: position.coords.longitude,
                });
            },
            (error) => {
                console.warn("[FletiZoneMap] Geolocation failed, using BR default:", error);
                map.setCenter(this.defaultCenter);
            },
            { timeout: 8000, maximumAge: 60000 }
        );
    },

    createPointMarker(map, latLng) {
        return new google.maps.Marker({
            position: latLng,
            map: map,
            clickable: false,
            optimized: true,
            zIndex: 999,
            icon: {
                path: google.maps.SymbolPath.CIRCLE,
                scale: 8,
                fillColor: "#14b19e",
                fillOpacity: 1,
                strokeWeight: 2,
                strokeColor: "#ffffff",
            },
        });
    },

    createInteractiveZoneDrawer(map, options = {}) {
        const self = this;
        const minimumPoints = options.minimumPoints || 3;
        const onPolygonChange = options.onPolygonChange || function () {};
        const onStartDrawing = options.onStartDrawing || function () {};
        let points = [];
        let markers = [];
        let previewLine = null;
        let previewPolygon = null;
        let polygon = null;
        let drawingActive = false;
        let clickListener = null;

        const stopClickListener = () => {
            if (clickListener) {
                google.maps.event.removeListener(clickListener);
                clickListener = null;
            }
        };

        const clearMarkersAndPreview = () => {
            markers.forEach((marker) => marker.setMap(null));
            markers = [];
            if (previewLine) {
                previewLine.setMap(null);
                previewLine = null;
            }
            if (previewPolygon) {
                previewPolygon.setMap(null);
                previewPolygon = null;
            }
            points = [];
        };

        const updatePreview = () => {
            if (previewLine) {
                previewLine.setMap(null);
                previewLine = null;
            }
            if (previewPolygon) {
                previewPolygon.setMap(null);
                previewPolygon = null;
            }

            if (points.length >= 3) {
                previewPolygon = new google.maps.Polygon({
                    paths: points,
                    strokeColor: "#14b19e",
                    strokeOpacity: 0.95,
                    strokeWeight: 2,
                    fillColor: "#14b19e",
                    fillOpacity: 0.18,
                    clickable: false,
                    map: map,
                });
            } else if (points.length >= 2) {
                previewLine = new google.maps.Polyline({
                    path: points,
                    strokeColor: "#14b19e",
                    strokeOpacity: 0.9,
                    strokeWeight: 3,
                    clickable: false,
                    map: map,
                });
            }
        };

        const syncFromPolygon = (poly) => {
            const formatted = self.setCoordinatesFromOverlay(poly);
            onPolygonChange(formatted, poly);
            return formatted;
        };

        const cancelDraft = () => {
            drawingActive = false;
            stopClickListener();
            clearMarkersAndPreview();
            map.setOptions({ draggable: true, draggableCursor: null, draggingCursor: null });
        };

        const clear = () => {
            cancelDraft();
            self.safeClearPolygon(polygon);
            polygon = null;
            $("#coordinates").val("");
            self.autoGrow("coordinates");
            self.setDrawStatus("zoneDrawHintIdle", "Drag the map, then click Start drawing.");
        };

        const addPoint = (latLng) => {
            if (!drawingActive || !latLng) {
                return;
            }

            // Click near the first point closes the zone (snap)
            if (points.length >= minimumPoints) {
                const first = points[0];
                if (self.distanceMeters(first, latLng) <= 40) {
                    finishDrawing();
                    return;
                }
            }

            points.push(latLng);
            markers.push(self.createPointMarker(map, latLng));
            updatePreview();

            const status = window.zoneMapMessages?.zoneDrawPointsCount || "Points placed: :count";
            if (points.length >= minimumPoints) {
                self.setDrawStatus(
                    "zoneDrawHintClose",
                    "Click Finish zone, or click again on the first point to close."
                );
            } else {
                self.setDrawStatus(null, status.replace(":count", String(points.length)));
            }
        };

        const startDrawing = () => {
            stopClickListener();
            cancelDraft();

            onStartDrawing();

            self.safeClearPolygon(polygon);
            polygon = null;
            $("#coordinates").val("");
            clearMarkersAndPreview();

            drawingActive = true;
            map.setOptions({
                draggable: true,
                draggableCursor: "crosshair",
                draggingCursor: "move",
            });
            self.setDrawStatus(
                "zoneDrawHintActive",
                "Click on the map to place points (minimum 3), then click Finish zone."
            );

            clickListener = google.maps.event.addListener(map, "click", (event) => {
                if (!drawingActive) {
                    return;
                }
                addPoint(event.latLng);
            });
        };

        const finishDrawing = () => {
            if (!drawingActive) {
                toastr.info(
                    window.zoneMapMessages?.zoneDrawStartFirst ||
                        "Click Start drawing first, then place points on the map."
                );
                return "";
            }

            drawingActive = false;
            stopClickListener();
            map.setOptions({ draggable: true, draggableCursor: null, draggingCursor: null });

            if (points.length < minimumPoints) {
                toastr.warning(window.zoneMapMessages?.minPoints);
                clearMarkersAndPreview();
                self.setDrawStatus("zoneDrawHintIdle", "Drag the map, then click Start drawing.");
                return "";
            }

            if (previewLine) {
                previewLine.setMap(null);
                previewLine = null;
            }
            if (previewPolygon) {
                previewPolygon.setMap(null);
                previewPolygon = null;
            }
            markers.forEach((marker) => marker.setMap(null));
            markers = [];

            const pathPoints = points.slice();
            points = [];

            // Explicitly close the ring for storage and display
            const first = pathPoints[0];
            const last = pathPoints[pathPoints.length - 1];
            if (first.lat() !== last.lat() || first.lng() !== last.lng()) {
                pathPoints.push(first);
            }

            polygon = new google.maps.Polygon({
                paths: pathPoints,
                map: map,
                ...self.polygonStyle,
            });

            self.attachPolygonPathListeners(polygon, () => syncFromPolygon(polygon));
            const formatted = syncFromPolygon(polygon);
            self.setDrawStatus(
                "zoneDrawHintDone",
                "Zone drawn. You can drag the corner points to adjust, then submit."
            );
            return formatted;
        };

        return {
            startDrawing,
            finishDrawing,
            clear,
            cancelDraft,
            getPolygon: () => polygon,
            setPolygon: (poly) => {
                polygon = poly;
            },
            isDrawing: () => drawingActive,
        };
    },

    bindZoneMapToolbar(drawer) {
        const startBtn = document.getElementById("fleti-zone-start-draw");
        const finishBtn = document.getElementById("fleti-zone-finish-draw");
        const clearBtn = document.getElementById("fleti-zone-clear-draw");
        if (!startBtn || !drawer) {
            return;
        }

        startBtn.addEventListener("click", (event) => {
            event.preventDefault();
            drawer.startDrawing();
            startBtn.classList.add("active");
        });

        finishBtn?.addEventListener("click", (event) => {
            event.preventDefault();
            drawer.finishDrawing();
            startBtn.classList.remove("active");
        });

        clearBtn?.addEventListener("click", (event) => {
            event.preventDefault();
            drawer.clear();
            startBtn.classList.remove("active");
        });
    },

    initSearchBox(map, inputId = "pac-input") {
        const input = document.getElementById(inputId);
        if (!input || !google.maps.places) {
            return null;
        }

        const searchBox = new google.maps.places.SearchBox(input);
        map.addListener("bounds_changed", () => {
            searchBox.setBounds(map.getBounds());
        });

        let markers = [];
        searchBox.addListener("places_changed", () => {
            const places = searchBox.getPlaces();
            if (!places || places.length === 0) {
                return;
            }

            markers.forEach((marker) => marker.setMap(null));
            markers = [];

            const bounds = new google.maps.LatLngBounds();
            places.forEach((place) => {
                if (!place.geometry || !place.geometry.location) {
                    return;
                }
                markers.push(
                    new google.maps.Marker({
                        map: map,
                        title: place.name,
                        position: place.geometry.location,
                        clickable: false,
                    })
                );
                if (place.geometry.viewport) {
                    bounds.union(place.geometry.viewport);
                } else {
                    bounds.extend(place.geometry.location);
                }
            });
            if (!bounds.isEmpty()) {
                map.fitBounds(bounds);
            }
        });

        return searchBox;
    },

    initZoneMapEditor(map, options = {}) {
        const drawer = this.createInteractiveZoneDrawer(map, options);
        this.bindZoneMapToolbar(drawer);
        return { drawer };
    },

    attachPolygonPathListeners(overlay, onChange) {
        if (!overlay || !overlay.getPath) {
            return;
        }

        const path = overlay.getPath();
        const notify = () => {
            if (typeof onChange === "function") {
                onChange();
            }
        };

        google.maps.event.addListener(path, "set_at", notify);
        google.maps.event.addListener(path, "insert_at", notify);
        google.maps.event.addListener(path, "remove_at", notify);
    },

    setPolygonsClickable(polygons, clickable) {
        if (!Array.isArray(polygons)) {
            return;
        }
        polygons.forEach((polygon) => {
            if (polygon && typeof polygon.setOptions === "function") {
                polygon.setOptions({ clickable: clickable });
            }
        });
    },

    loadExistingZones(map, url, polygonStore) {
        if (!map || !url) {
            return;
        }

        $.get({
            url: url,
            dataType: "json",
            success: (data) => {
                if (!Array.isArray(data)) {
                    return;
                }
                for (let i = 0; i < data.length; i++) {
                    const polygon = new google.maps.Polygon({
                        paths: data[i],
                        strokeColor: "#FF0000",
                        strokeOpacity: 0.8,
                        strokeWeight: 2,
                        fillColor: "#FF0000",
                        fillOpacity: 0.1,
                        clickable: false,
                    });
                    polygon.setMap(map);
                    if (Array.isArray(polygonStore)) {
                        polygonStore.push(polygon);
                    }
                }
            },
            error: (xhr) => {
                console.warn("[FletiZoneMap] Could not load existing zones:", xhr.status);
            },
        });
    },

    ensureMapCoreReady() {
        return (
            typeof google !== "undefined" &&
            google.maps &&
            typeof google.maps.Map === "function"
        );
    },

    ensureMapsReady() {
        return this.ensureMapCoreReady();
    },

    runWhenReady(callback, attempt = 0) {
        if (this.ensureMapCoreReady()) {
            callback();
            return;
        }
        if (attempt >= 40) {
            this.showMapsError();
            return;
        }
        window.setTimeout(() => this.runWhenReady(callback, attempt + 1), 150);
    },

    showMapsError(message) {
        const canvas = document.getElementById("map-canvas");
        if (!canvas) {
            return;
        }
        const text =
            message ||
            window.zoneMapMessages?.mapsError ||
            "Google Maps failed to load. Check the API key and enable Maps JavaScript API.";
        canvas.innerHTML =
            '<div class="d-flex align-items-center justify-content-center h-100 p-4 text-center text-danger" style="min-height:360px;background:#f8f9fa;">' +
            text +
            "</div>";
    },
};
