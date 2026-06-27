@push('css_or_js')
    <style>
        #trip-map-inline,
        #trip-map-layer {
            width: 100%;
        }

        #trip-map-inline {
            min-height: 240px;
        }

        #trip-map-layer {
            min-height: 520px;
        }

        .trip-map-preview {
            border: 1px solid rgba(0, 0, 0, 0.08);
            cursor: pointer;
        }

        .trip-map-preview__overlay {
            position: absolute;
            inset: auto 0 0 0;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.95) 35%);
            color: #334155;
            font-size: 12px;
            font-weight: 600;
            pointer-events: none;
        }

        .trip-map-legend {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #64748b;
        }

        .trip-map-legend__dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 4px;
        }

        .trip-map-legend__dot--pickup {
            background: #14b19e;
        }

        .trip-map-legend__dot--destination {
            background: #ef4444;
        }

        .trip-map-legend__line {
            width: 18px;
            height: 3px;
            border-radius: 999px;
            display: inline-block;
            margin-right: 4px;
            background: #4285F4;
            vertical-align: middle;
        }
    </style>
@endpush
<div class="col-lg-4">
    @if(count($safetyAlerts) > 0)
        <div class="max-h-340px overflow-auto mb-3">
            @foreach($safetyAlerts as $safetyAlert)
                @php
                    $userType = match (true) {
                        $safetyAlert?->sentBy?->user_type == 'driver' && ($safetyAlert?->trip?->current_status == 'ongoing' || $safetyAlert?->trip?->current_status == 'completed') => 'driver-on-trip',
                        $safetyAlert?->sentBy?->user_type == 'driver' => 'driver-idle',
                        default => 'all-customer',
                    };
                    $route = route('admin.fleet-map', ['type' => $userType]) . '?zone_id=' . $safetyAlert?->trip?->zone_id;
                @endphp
                <div class="card {{ $loop->last ? '' : 'mb-3' }}">
                    <div class="card-body">
                        <h5 class="text-center mb-3 text-capitalize">{{translate('Safety_Alert')}} <span
                                class="fw-medium">({{ $safetyAlert?->number_of_alert }})</span></h5>
                        <hr>
                        <div class="d-flex gap-3 justify-content-between flex-wrap fs-12 mb-3">
                            <div class="d-flex flex-column gap-10px">
                                <h6 class="fs-12">{{ translate('Sent By') }} : <span
                                        class="fw-normal">{{ $safetyAlert->sentBy?->full_name ?? $safetyAlert->sentBy?->first_name . $safetyAlert->sentBy?->last_name }}</span>
                                </h6>
                                @if($safetyAlert?->resolved_by)
                                    <h6 class="fs-12">{{ translate('Resolved By') }}: <span class="fw-normal">
                                   {{ $safetyAlert?->solvedBy?->user_type == 'admin-employee' ? 'Employee' : $safetyAlert?->solvedBy?->user_type }}
                                            {{ $safetyAlert?->solvedBy?->user_type == 'admin-employee' && $safetyAlert?->solvedBy?->id ? '(' . $safetyAlert?->solvedBy?->first_name. ' ' . $safetyAlert?->solvedBy?->last_name . ')': ' ' }}
                                </span></h6>
                                @endif
                            </div>
                            <span>{{date('d F Y', strtotime($safetyAlert->created_at))}}, {{date('h:i a', strtotime($safetyAlert->created_at))}}</span>
                        </div>
                        @if($safetyAlert?->reason || $safetyAlert?->comment)
                            <div class="bg-danger-light rounded  mb-3 px-2 py-3">
                                <ol class="d-flex flex-column gap-2 mb-0">
                                    @if($safetyAlert?->reason)
                                        @foreach($safetyAlert?->reason as $reason)
                                            <li>{{ $reason }}</li>
                                        @endforeach
                                    @endif
                                    @if($safetyAlert?->comment)
                                        <li>{{  $safetyAlert?->comment }}</li>
                                    @endif
                                </ol>
                            </div>
                        @endif
                        <div class="mb-3">
                            <h6 class="fs-12">{{ translate('Alert Location') }}</h6>
                            <p class="fs-12">{{ $safetyAlert?->alert_location }}</p>
                        </div>
                        @if($safetyAlert?->resolved_location)
                            <div class="{{ $safetyAlert?->status == PENDING ? 'mb-3' : '' }}">
                                <h6 class="fs-12">{{ translate('Resolved Location') }}</h6>
                                <p class="fs-12">{{ $safetyAlert?->resolved_location }}</p>
                            </div>
                        @endif
                        @if($safetyAlert?->status == PENDING)
                            <div class="d-flex gap-2 justify-content-between flex-wrap">
                                <a href="{{ $route }}"
                                   class="btn btn-secondary flex-grow-1 w-100px justify-content-center fw-semibold show-safety-alert-user-details"
                                   data-user-id="{{ $safetyAlert?->sentBy?->id }}">
                                    {{ translate('Fleet View') }}
                                </a>
                                <form action="{{ route('admin.safety-alert.mark-as-solved', $safetyAlert->id) }}"
                                      method="post"
                                      class="btn btn-primary fw-semibold flex-grow-1 w-100px justify-content-center">
                                    @csrf
                                    @method('PUT')
                                    <button type="submit"
                                            class="btn btn-primary m-0 p-0">
                                        {{ translate('Mark as Solved') }}
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if($trip?->parcelRefund)
        <div class="d-flex gap-10px mb-10px">
            @if($trip->parcelRefund->status == PENDING || $trip->parcelRefund->status == APPROVED )
                <button class="btn btn--cancel flex-grow-1 w-100px justify-content-center fw-semibold"
                        type="button"
                        id="deniedButtonParcelRefund"
                        data-url="{{route('admin.trip.refund.denied', [$trip->parcelRefund->id])}}"
                        data-icon="{{ dynamicAsset('public/assets/admin-module/img/denied-icon.png') }}"
                        data-title="{{ translate('Are you sure to Deny the Refund Request')."?" }}"
                        data-sub-title="{{translate("Once you deny the request, the customer will not be refunded the amount he asked for.")}}"
                        data-confirm-btn="{{translate("Deny")}}"
                        data-input-title="{{translate("Deny Note")}}"
                        class="btn btn-outline-danger btn-action d-flex justify-content-center align-items-center"
                >{{ translate('Deny') }}</button>
            @endif
            @if($trip->parcelRefund->status == PENDING || $trip->parcelRefund->status == DENIED )
                <button class="btn btn-primary cmn_focus flex-grow-1 w-100px justify-content-center fw-semibold"
                        type="button"
                        id="approvalButtonParcelRefund"
                        data-url="{{route('admin.trip.refund.approved', [$trip->parcelRefund->id])}}"
                        data-icon="{{ dynamicAsset('public/assets/admin-module/img/approval-icon.png') }}"
                        data-title="{{ translate('Are you sure to Approve the Refund Request')."?" }}"
                        data-sub-title="{{translate("The customer has requested a refund of")}}  <strong>{{set_currency_symbol($trip->parcelRefund->parcel_approximate_price)}}</strong> {{translate("for this parcel.")}}"
                        data-confirm-btn="{{translate("Approve")}}"
                        data-input-title="{{translate("Approval Note")}}"
                        class="btn btn-outline-success btn-action d-flex justify-content-center align-items-center"
                >{{ translate('Approve') }}</button>
            @endif

            @if($trip->parcelRefund->status == APPROVED )
                <button class="btn btn-primary cmn_focus flex-grow-1 w-100px justify-content-center fw-semibold"
                        id="parcelRefundButton"
                        data-amount="{{$trip->parcelRefund->parcel_approximate_price}}"
                        data-url="{{route('admin.trip.refund.store', [$trip->parcelRefund->id])}}"
                        type="button">{{ translate('Make Refund') }}</button>
            @endif
        </div>
    @endif
    <div class="card">
        <div class="card-body">
            @php
                $displayTripStatus = ($trip->current_status == PENDING && $trip->ride_request_type == SCHEDULED)
                    ? SCHEDULED
                    : ($trip->current_status ?? '—');
                $displayPaymentStatus = $trip->payment_status ?? '—';
                $tripDistance = $trip->current_status == 'completed'
                    ? ($trip->actual_distance ?? $trip->estimated_distance)
                    : ($trip->estimated_distance ?? $trip->actual_distance);
                $pickupCoordinates = $trip->coordinate?->pickup_coordinates;
                $destinationCoordinates = $trip->coordinate?->destination_coordinates;
                $hasMapCoords = $pickupCoordinates && $destinationCoordinates;
                $mapApiKey = businessConfig(GOOGLE_MAP_API)?->value['map_api_key'] ?? null;
                $encodedPolyline = $routePolyline ?? $trip->encoded_polyline;
            @endphp
            <h5 class="text-center mb-3 text-capitalize">{{ translate('Trip Details') }}</h5>

            <div class="mb-3">
                <label class="mb-2 d-block">{{ translate('trip_status') }}</label>
                <div class="form-control bg-light text-capitalize">{{ translate($displayTripStatus) }}</div>
            </div>

            <div class="mb-4">
                <label class="mb-2 d-block">{{ translate('payment_status') }}</label>
                <div class="form-control bg-light text-capitalize">{{ translate($displayPaymentStatus) }}</div>
            </div>
            @if($hasMapCoords && $mapApiKey)
            <div class="mb-4">
                <label class="mb-2 d-block">{{ translate('view_in_map') }}</label>
                <div class="trip-map-preview position-relative rounded-10 overflow-hidden mb-2"
                     data-bs-toggle="modal"
                     data-bs-target="#tripMapModal"
                     title="{{ translate('view_in_map') }}">
                    <div id="trip-map-inline"></div>
                    <div class="trip-map-preview__overlay">
                        <i class="bi bi-arrows-fullscreen"></i>
                        {{ translate('click_to_expand_map') }}
                    </div>
                </div>
                <div class="trip-map-legend mb-2">
                    <span><span class="trip-map-legend__dot trip-map-legend__dot--pickup"></span>{{ translate('pickup') }}</span>
                    <span><span class="trip-map-legend__dot trip-map-legend__dot--destination"></span>{{ translate('destination') }}</span>
                    <span><span class="trip-map-legend__line"></span>{{ translate('route') }}</span>
                </div>
                <button type="button"
                        class="btn btn-outline-primary w-100"
                        data-bs-toggle="modal"
                        data-bs-target="#tripMapModal">
                    <i class="bi bi-map"></i> {{ translate('view_in_map') }}
                </button>
            </div>

            <div class="modal fade" id="tripMapModal" tabindex="-1" aria-labelledby="tripMapModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="tripMapModalLabel">{{ translate('view_in_map') }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-0">
                            <div id="trip-map-layer"></div>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div>
                <ul class="list-icon">
                    <li>
                        <div class="media gap-2">
                            <img width="18" src="{{dynamicAsset('public/assets/admin-module/img/svg/gps.svg')}}" class="svg"
                                 alt="">
                            <div class="media-body">{{ $trip->coordinate?->pickup_address ?? '—' }}</div>
                        </div>
                    </li>
                    <li>
                        <div class="media gap-2">
                            <img width="18" src="{{dynamicAsset('public/assets/admin-module/img/svg/map-nav.svg')}}"
                                 class="svg" alt="">
                            <div class="media-body">
                                <div>{{ $trip->coordinate?->destination_address ?? '—' }}</div>
                                @if($trip->entrance)
                                    <a href="#" class="text-primary d-flex">{{$trip->entrance}}</a>

                                @endif
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="media gap-2">
                            <img width="18" src="{{dynamicAsset('public/assets/admin-module/img/svg/distance.svg')}}"
                                 class="svg" alt="">
                            <div class="media-body text-capitalize">
                                {{ translate('total_distance') }}
                                @if(filled($tripDistance))
                                    - {{ $tripDistance }} {{ translate('km') }}
                                @else
                                    - {{ translate('unavailable') }}
                                @endif
                            </div>
                        </div>
                    </li>
                    @if($trip->pickup_note)
                        <li>
                            <div class="pickup-note p-10px fs-14 title-color bg-info-5  rounded-10">
                                <span class="text-info">{{ translate('Pickup Note') }}:</span> {{ $trip->pickup_note }}
                            </div>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
    <div class="modal fade" id="make-refund">
        <div class="modal-dialog modal-lg extra-fare-setup-modal">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Make Refund</h5>
                    <button type="submit" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-4">
                            <label for="refund_amount" class="form-label">{{translate('Refund Amount')}} ($) <i
                                    class="bi bi-info-circle-fill text-primary"></i></label>
                            <input type="text" class="form-control" id="refund_amount"
                                   placeholder="{{translate("Ex : 10")}}">
                        </div>
                        <label class="form-label">{{translate('Refund Method')}} <i
                                class="bi bi-info-circle-fill text-primary"></i></label>
                        <div class="border rounded border-ced4da p-3 mb-4">
                            <div class="d-flex flex-wrap gap-5">
                                <div>
                                    <input type="radio" name="refund_method" id="pay-manually" checked>
                                    <label class="form-check-label" for="pay-manually">Pay Manually</label>
                                </div>
                                <div>
                                    <input type="radio" name="refund_method" id="pay-in-wallet">
                                    <label class="form-check-label" for="pay-in-wallet">Pay in Wallet</label>
                                </div>
                                <div>
                                    <input type="radio" name="refund_method" id="create-refund-coupon">
                                    <label class="form-check-label" for="create-refund-coupon">Create a refund
                                        Coupon</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="refund_reason" class="form-label">{{translate('Refund Note')}}</label>
                            <textarea class="form-control" id="refund_reason" rows="3"
                                      placeholder="{{translate('Type a refund note for your customer')}}"></textarea>
                        </div>
                        <div class="d-flex gap-10px justify-content-end">
                            <button class="btn btn-secondary cmn_reset" data-bs-dismiss="modal"
                                    type="button">{{ translate('Cancel') }}</button>
                            <button class="btn btn-primary cmn_focus" data-bs-dismiss="modal"
                                    type="button">{{ translate('Make Refund') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @php
        $pickupProofImages = $trip?->proofImage?->pickup_proof_images ?? [];
        $deliveryProofImages = $trip?->proofImage?->delivery_proof_images ?? [];
    @endphp
    @if($trip->type == PARCEL && (count($pickupProofImages) || count($deliveryProofImages)))
        <div class="card mt-3">
            <div class="card-body">
                <h6 class="fs-13 mb-20">{{ translate('Delivery Proof Image') }}</h6>
                @if(count($pickupProofImages))
                    <div class="bg-F6F6F6 rounded p-10px mb-3">
                        <h6 class="fs-12 fw-medium mb-10px">{{ translate('During Pickup') }}</h6>
                        <div class="row g-3">
                            @foreach($pickupProofImages as $pickupImage)
                                <div class="col-sm-3 col-6">
                                    <a class="proof-file-item image-preview"
                                       data-image="{{ dynamicStorage('storage/app/public/trip/parcel/proof/pickup/' . $pickupImage) }}">
                                        <div class="proof_image h-80px rounded overflow-hidden">
                                            <img src="{{ onErrorImage(
                                                    $pickupImage,
                                                    dynamicStorage('storage/app/public/trip/parcel/proof/pickup/' . $pickupImage),
                                                    dynamicAsset('public/assets/admin-module/img/avatar/avatar.png'),
                                                    'trip/parcel/proof/pickup/',
                                                ) }}"
                                                 alt="" class="object-cover w-100 h-100 rounded">
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if(count($deliveryProofImages))
                    <div class="bg-F6F6F6 rounded p-10px">
                        <h6 class="fs-12 fw-medium mb-10px">{{ translate('After Complete') }}</h6>
                        <div class="row g-3">
                            @foreach($deliveryProofImages as $deliveryImage)
                                <div class="col-sm-3 col-6">
                                    <a class="proof-file-item image-preview"
                                       data-image="{{ dynamicStorage('storage/app/public/trip/parcel/proof/delivery/' . $deliveryImage) }}">
                                        <div class="proof_image h-80px rounded overflow-hidden">
                                            <img src="{{ onErrorImage(
                                                    $deliveryImage,
                                                    dynamicStorage('storage/app/public/trip/parcel/proof/delivery/' . $deliveryImage),
                                                    dynamicAsset('public/assets/admin-module/img/avatar/avatar.png'),
                                                    'trip/parcel/proof/delivery/',
                                                ) }}"
                                                 alt="" class="object-cover w-100 h-100 rounded">
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('script')
    @if($hasMapCoords && $mapApiKey)
    <script>
        (function () {
            const pickup = {
                lat: {{ $pickupCoordinates->latitude }},
                lng: {{ $pickupCoordinates->longitude }}
            };
            const destination = {
                lat: {{ $destinationCoordinates->latitude }},
                lng: {{ $destinationCoordinates->longitude }}
            };
            const encodedPolyline = @json($encodedPolyline);
            const tripMaps = {};
            let mapsScriptRequested = false;

            function markerIcon(color) {
                return {
                    path: google.maps.SymbolPath.CIRCLE,
                    fillColor: color,
                    fillOpacity: 1,
                    strokeColor: '#ffffff',
                    strokeWeight: 2,
                    scale: 9
                };
            }

            function fitMapToPoints(map, points) {
                if (!map || !points.length) {
                    return;
                }

                const bounds = new google.maps.LatLngBounds();
                points.forEach(function (point) {
                    bounds.extend(point);
                });
                map.fitBounds(bounds);

                google.maps.event.addListenerOnce(map, 'bounds_changed', function () {
                    if (map.getZoom() > 16) {
                        map.setZoom(16);
                    }
                });
            }

            function addTripMarkers(map) {
                new google.maps.Marker({
                    position: pickup,
                    map: map,
                    title: @json($trip->coordinate?->pickup_address ?? translate('pickup')),
                    icon: markerIcon('#14b19e'),
                    zIndex: 2
                });
                new google.maps.Marker({
                    position: destination,
                    map: map,
                    title: @json($trip->coordinate?->destination_address ?? translate('destination')),
                    icon: markerIcon('#ef4444'),
                    zIndex: 2
                });
            }

            function drawStraightRoute(map) {
                const path = [pickup, destination];
                new google.maps.Polyline({
                    path: path,
                    geodesic: true,
                    strokeColor: '#4285F4',
                    strokeOpacity: 0.85,
                    strokeWeight: 4,
                    map: map,
                    zIndex: 1
                });
                fitMapToPoints(map, path);
            }

            function drawEncodedRoute(map) {
                const path = google.maps.geometry.encoding.decodePath(encodedPolyline);
                if (!path.length) {
                    drawStraightRoute(map);
                    return;
                }

                new google.maps.Polyline({
                    path: path,
                    geodesic: true,
                    strokeColor: '#4285F4',
                    strokeOpacity: 0.95,
                    strokeWeight: 5,
                    map: map,
                    zIndex: 1
                });
                fitMapToPoints(map, path);
            }

            function drawTripRoute(map) {
                if (encodedPolyline) {
                    drawEncodedRoute(map);
                    return;
                }

                drawStraightRoute(map);
            }

            function renderTripMap(layerId, options) {
                const mapLayer = document.getElementById(layerId);
                if (!mapLayer || !window.google?.maps) {
                    return null;
                }

                const settings = Object.assign({
                    compact: false,
                    gestureHandling: 'greedy'
                }, options || {});

                mapLayer.innerHTML = '';
                const map = new google.maps.Map(mapLayer, {
                    zoom: 13,
                    center: pickup,
                    mapTypeId: google.maps.MapTypeId.ROADMAP,
                    mapTypeControl: !settings.compact,
                    fullscreenControl: !settings.compact,
                    streetViewControl: !settings.compact,
                    zoomControl: true,
                    gestureHandling: settings.gestureHandling,
                    disableDefaultUI: settings.compact
                });

                addTripMarkers(map);
                drawTripRoute(map);
                tripMaps[layerId] = map;

                return map;
            }

            function resizeTripMap(layerId) {
                const map = tripMaps[layerId];
                if (!map) {
                    return;
                }

                google.maps.event.trigger(map, 'resize');

                if (encodedPolyline) {
                    const path = google.maps.geometry.encoding.decodePath(encodedPolyline);
                    fitMapToPoints(map, path.length ? path : [pickup, destination]);
                    return;
                }

                fitMapToPoints(map, [pickup, destination]);
            }

            function loadGoogleMaps(callback) {
                if (window.google?.maps?.geometry) {
                    callback();
                    return;
                }

                if (mapsScriptRequested) {
                    const waitForMaps = setInterval(function () {
                        if (window.google?.maps?.geometry) {
                            clearInterval(waitForMaps);
                            callback();
                        }
                    }, 100);
                    return;
                }

                mapsScriptRequested = true;
                window.initTripDetailsMap = callback;
                const script = document.createElement('script');
                script.src = 'https://maps.googleapis.com/maps/api/js?key={{ $mapApiKey }}&libraries=geometry&callback=initTripDetailsMap';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }

            function initInlineTripMap() {
                renderTripMap('trip-map-inline', {
                    compact: true,
                    gestureHandling: 'none'
                });
            }

            function openTripMapModal() {
                renderTripMap('trip-map-layer', {
                    compact: false,
                    gestureHandling: 'greedy'
                });

                setTimeout(function () {
                    resizeTripMap('trip-map-layer');
                }, 250);
            }

            loadGoogleMaps(function () {
                initInlineTripMap();

                const tripMapModal = document.getElementById('tripMapModal');
                if (tripMapModal) {
                    tripMapModal.addEventListener('shown.bs.modal', openTripMapModal);
                }
            });
        })();
    </script>
    @endif

    <script>
        $(document).ready(function () {
            let showSafetyAlertUserDetails = $('.show-safety-alert-user-details');

            showSafetyAlertUserDetails.on('click', function () {
                localStorage.setItem('safetyAlertUserDetailsStatus', true);
                localStorage.setItem('safetyAlertUserIdFromTrip', $(this).data('user-id'));
            });
        })
    </script>
@endpush
