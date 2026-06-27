@extends('adminmodule::layouts.master')

@section('title', translate('Edit_Zone_Setup'))

@section('content')
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="d-flex align-items-center gap-3 justify-content-between mb-4">
                <h2 class="fs-22 text-capitalize">{{ translate('zone_setup') }}</h2>
            </div>
            <form id="zone_form" action="{{ route('admin.zone.update', ['id'=>$zone->id]) }}"
                  enctype="multipart/form-data" method="POST">
                @csrf
                @method('put')
                <div class="row mb-3">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-body">
                                <div class="row justify-content-between">
                                    <div class="col-lg-5 col-xl-4 mb-5 mb-lg-0">
                                        <h5 class="text-primary text-uppercase mb-4">{{ translate('instructions') }}</h5>
                                        <div class="d-flex flex-column">
                                            <p>{{ translate('create_zone_by_click_on_map_and_connect_the_dots_together') }}</p>

                                            <div class="media mb-2 gap-3 align-items-center">
                                                <img
                                                    src="{{dynamicAsset('public/assets/admin-module/img/map-drag.png') }}"
                                                    alt="">
                                                <div class="media-body ">
                                                    <p>{{ translate('use_this_to_drag_map_to_find_proper_area') }}</p>
                                                </div>
                                            </div>

                                            <div class="media gap-3 align-items-center">
                                                <img
                                                    src="{{dynamicAsset('public/assets/admin-module/img/map-draw.png') }}"
                                                    alt="">
                                                <div class="media-body ">
                                                    <p>{{ translate('click_this_icon_to_start_pin_points_in_the_map_and_connect_them_
                                                            to_draw_a_
                                                            zone_._Minimum_3_points_required') }}</p>
                                                </div>
                                            </div>
                                            <div class="map-img mt-4">
                                                <img
                                                    src="{{ dynamicAsset('public/assets/admin-module/img/instructions.gif') }}"
                                                    alt="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-7">
                                        <div class="mb-4">
                                            <label for="zone_name"
                                                   class="form-label text-capitalize">{{ translate('zone_name') }} <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="name" id="zone_name"
                                                   value="{{ $zone->name }}" placeholder="{{ translate('ex') }}: {{ translate('Dhanmondi') }}" required>
                                        </div>

                                        @php
                                            $zoneCoordinatePairs = [];
                                            foreach ($zone->coordinates[0]->toArray()['coordinates'] as $key => $coords) {
                                                if (count($zone->coordinates[0]->toArray()['coordinates']) != $key + 1) {
                                                    $zoneCoordinatePairs[] = '(' . $coords[1] . ',' . $coords[0] . ')';
                                                }
                                            }
                                            $zoneCoordinatesValue = implode(',', $zoneCoordinatePairs);
                                        @endphp
                                        <input type="hidden" name="coordinates" id="coordinates" value="{{ $zoneCoordinatesValue }}">

                                        @include('zonemanagement::admin.zone.partials._map-toolbar')
                                        @include('zonemanagement::admin.zone.partials._map-search')

                                        <!-- Start Map -->
                                        <div class="map-warper map-pac-controller rounded">
                                            <div id="map-canvas" class="map-height"></div>
                                        </div>
                                        <!-- End Map -->
                                    </div>

                                    <div class="d-flex justify-content-end gap-3 mt-3">
                                        <button class="btn btn-secondary cmn_reset" type="reset" id="reset_btn">
                                            {{ translate('reset') }}
                                        </button>
                                        <button class="btn btn-primary cmn_focus" type="submit">
                                            {{ translate('update') }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
    <!-- End Main Content -->
@endsection

@push('script')
    @include('zonemanagement::admin.zone.partials._map-loader')
    <script>
        "use strict";
        window.zoneMapMessages = {
            defineZone: @json(translate('please_define_zone')),
            minPoints: @json(translate('click_this_icon_to_start_pin_points_in_the_map_and_connect_the_dots_together_to_draw_a_zone_._Minimum_3_points_required')),
            mapsError: @json(translate('google_maps_failed_to_load_check_api_key')),
            zoneDrawHintIdle: @json(translate('zone_draw_hint_idle')),
            zoneDrawHintActive: @json(translate('zone_draw_hint_active')),
            zoneDrawHintDone: @json(translate('zone_draw_hint_done')),
            zoneDrawStartFirst: @json(translate('zone_draw_start_first')),
            zoneDrawPointsCount: @json(translate('zone_draw_points_count')),
            zoneDrawHintClose: @json(translate('zone_draw_hint_close')),
        };

        FletiZoneMap.autoGrow('coordinates');

        $('#zone_form').on('submit', function (e) {
            return FletiZoneMap.validateFormSubmit(e, 3, zoneEditor?.drawer);
        });

        let map;
        let zoneEditor;
        let lastpolygon = null;
        let zonePolygon = null;
        let polygons = [];

        function initialize() {
            let myLatlng = new google.maps.LatLng({{trim(explode(' ',$zone->center)[1], 'POINT()') }}, {{trim(explode(' ',$zone->center)[0], 'POINT()') }});
            let myOptions = {
                zoom: 13,
                center: myLatlng,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                gestureHandling: "greedy",
            };
            map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);

            const polygonCoords = [
                    @foreach($area['coordinates'] as $coords)
                {
                    lat: {{$coords[1]}}, lng: {{$coords[0]}}
                },
                @endforeach
            ];

            zonePolygon = new google.maps.Polygon({
                paths: polygonCoords,
                strokeColor: "#14b19e",
                strokeOpacity: 0.9,
                strokeWeight: 2,
                fillColor: "#14b19e",
                fillOpacity: 0.2,
                editable: true,
            });

            zonePolygon.setMap(map);
            lastpolygon = zonePolygon;

            const bounds = new google.maps.LatLngBounds();
            zonePolygon.getPaths().forEach(function (path) {
                path.forEach(function (latlng) {
                    bounds.extend(latlng);
                });
            });
            map.fitBounds(bounds);

            const initialCoords = FletiZoneMap.formatPathToCoordinates(zonePolygon.getPath());
            if (initialCoords) {
                $("#coordinates").val(initialCoords);
                FletiZoneMap.autoGrow('coordinates');
            }

            zoneEditor = FletiZoneMap.initZoneMapEditor(map, {
                minimumPoints: 3,
                onStartDrawing: function () {
                    FletiZoneMap.safeClearPolygon(lastpolygon);
                    FletiZoneMap.safeClearPolygon(zonePolygon);
                    lastpolygon = null;
                    zonePolygon = null;
                    FletiZoneMap.setPolygonsClickable(polygons, false);
                },
                onPolygonChange: function (formatted, overlay) {
                    FletiZoneMap.safeClearPolygon(lastpolygon);
                    lastpolygon = overlay;
                    zonePolygon = overlay;
                    FletiZoneMap.setPolygonsClickable(polygons, false);
                    if (!FletiZoneMap.hasMinimumPoints(formatted, 3)) {
                        toastr.warning(window.zoneMapMessages.minPoints);
                    }
                },
            });
            zoneEditor.drawer.setPolygon(zonePolygon);
            FletiZoneMap.attachPolygonPathListeners(zonePolygon, () => {
                FletiZoneMap.setCoordinatesFromOverlay(zonePolygon);
            });

            FletiZoneMap.initSearchBox(map);

            FletiZoneMap.loadExistingZones(
                map,
                '{{route('admin.zone.get-zones',[$zone->id])}}',
                polygons
            );
        }

        window.fletiZoneMapInitialize = function () {
            if (!FletiZoneMap.ensureMapsReady()) {
                FletiZoneMap.showMapsError();
                return;
            }
            initialize();
        };

        $('#reset_btn').click(function () {
            $('#name').val(null);
            if (zoneEditor?.drawer) {
                zoneEditor.drawer.clear();
            }
            FletiZoneMap.safeClearPolygon(lastpolygon);
            lastpolygon = null;
            $('#coordinates').val(null);
            FletiZoneMap.autoGrow('coordinates');
        })

    </script>
@endpush
