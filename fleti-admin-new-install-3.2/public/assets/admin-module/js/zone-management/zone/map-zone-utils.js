"use strict";

/**
 * Fleti zone map helpers — shared by zone index and edit views.
 */
const FletiZoneMap = {
    defaultCenter: { lat: -23.55052, lng: -46.633308 }, // São Paulo — fallback for Fleti (BR)

    autoGrow(elementId) {
        const element = document.getElementById(elementId || "coordinates");
        if (!element) {
            return;
        }
        element.style.height = "5px";
        element.style.height = element.scrollHeight + "px";
    },

    formatPathToCoordinates(path) {
        const points = path.getArray();
        if (!points || points.length < 3) {
            return "";
        }
        return points
            .map((latLng) => `(${latLng.lat()},${latLng.lng()})`)
            .join(",");
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
        $("#coordinates").val(formatted);
        this.autoGrow("coordinates");
        return formatted;
    },

    validateFormSubmit(event, minimumPoints = 3) {
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
};
