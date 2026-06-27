@php
    $googleMapConfig = businessConfig(GOOGLE_MAP_API)?->value ?? [];
    $mapClientKey = trim((string) ($googleMapConfig['map_api_key'] ?? ''));
    $mapServerKey = trim((string) ($googleMapConfig['map_api_key_server'] ?? ''));
    $map_key = $mapClientKey !== '' ? $mapClientKey : $mapServerKey;
@endphp
<script src="{{ dynamicAsset('public/assets/admin-module/js/zone-management/zone/map-zone-utils.js') }}"></script>
@if($map_key === '')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.FletiZoneMap) {
                FletiZoneMap.showMapsError(@json(translate('configure_google_map_api_in_business_settings')));
            }
        });
    </script>
@else
    <script>
        window.gm_authFailure = function () {
            if (window.FletiZoneMap) {
                FletiZoneMap.showMapsError(@json(translate('google_maps_failed_to_load_check_api_key')));
            }
        };
        window.fletiZoneMapsReady = function () {
            FletiZoneMap.runWhenReady(function () {
                if (typeof window.fletiZoneMapInitialize === 'function') {
                    window.fletiZoneMapInitialize();
                }
            });
        };
    </script>
    <script async defer
            src="https://maps.googleapis.com/maps/api/js?key={{ $map_key }}&libraries=places&v=weekly&loading=async&callback=fletiZoneMapsReady"></script>
@endif
