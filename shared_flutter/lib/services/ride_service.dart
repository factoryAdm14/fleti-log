import '../core/api_constants.dart';
import '../models/fare_option.dart';
import '../models/geo_point.dart';
import '../models/parcel_category.dart';
import 'api_service.dart';

class RideService {
  RideService(this._api);

  final ApiService _api;

  Future<List<FareOption>> estimateFare({
    required GeoPoint pickup,
    required GeoPoint destination,
    required GeoPoint current,
    String type = 'ride_request',
    String rideRequestType = 'regular',
    String? parcelCategoryId,
    double? parcelWeight,
  }) async {
    final body = <String, dynamic>{
      'pickup_coordinates': '[${pickup.latitude},${pickup.longitude}]',
      'destination_coordinates': '[${destination.latitude},${destination.longitude}]',
      'type': type,
      'ride_request_type': rideRequestType,
      'pickup_address': pickup.address,
      'destination_address': destination.address,
      'intermediate_coordinates': '',
      'scheduled_at': '',
    };
    if (type == 'parcel') {
      body['parcel_weight'] = parcelWeight ?? 1;
      if (parcelCategoryId != null && parcelCategoryId.isNotEmpty) {
        body['parcel_category_id'] = parcelCategoryId;
      }
    }

    final response = await _api.post(
      ApiConstants.customerEstimatedFare,
      body: body,
    );

    final data = response.data?['data'];
    if (data is List) {
      return data.whereType<Map<String, dynamic>>().map(FareOption.fromJson).toList();
    }
    if (data is Map<String, dynamic>) {
      final fare = FareOption.fromJson(data);
      if (parcelCategoryId != null && fare.parcelCategoryId == null) {
        return [
          FareOption(
            vehicleCategoryId: fare.vehicleCategoryId,
            vehicleCategoryType: fare.vehicleCategoryType,
            estimatedFare: fare.estimatedFare,
            estimatedDistance: fare.estimatedDistance,
            estimatedDuration: fare.estimatedDuration,
            zoneId: fare.zoneId,
            polyline: fare.polyline,
            areaId: fare.areaId,
            extraEstimatedFare: fare.extraEstimatedFare,
            extraDiscountFare: fare.extraDiscountFare,
            extraDiscountAmount: fare.extraDiscountAmount,
            extraReturnFee: fare.extraReturnFee,
            extraCancellationFee: fare.extraCancellationFee,
            extraFareAmount: fare.extraFareAmount,
            extraFareFee: fare.extraFareFee,
            surgeMultiplier: fare.surgeMultiplier,
            returnFee: fare.returnFee,
            cancellationFee: fare.cancellationFee,
            parcelCategoryId: parcelCategoryId,
          ),
        ];
      }
      return [fare];
    }
    return [];
  }

  Future<List<ParcelCategory>> fetchParcelCategories() async {
    final response = await _api.get('${ApiConstants.baseUrl}/api/customer/parcel/category?limit=50&offset=1');
    final data = response.data?['data'];
    if (data is! List) return [];
    return data.whereType<Map<String, dynamic>>().map(ParcelCategory.fromJson).toList();
  }

  Future<String> createRide({
    required GeoPoint pickup,
    required GeoPoint destination,
    required GeoPoint current,
    required FareOption fare,
    required String paymentMethod,
    String note = '',
    bool bid = false,
    String type = 'ride_request',
    String? parcelCategoryId,
    double? parcelWeight,
    String? senderName,
    String? senderPhone,
    String? receiverName,
    String? receiverPhone,
    String payer = 'sender',
  }) async {
    final isParcel = type == 'parcel';
    final body = <String, dynamic>{
      'pickup_coordinates': pickup.coordinatesJson,
      'destination_coordinates': destination.coordinatesJson,
      'customer_coordinates': current.coordinatesJson,
      'customer_request_coordinates': current.coordinatesJson,
      'estimated_distance': fare.estimatedDistance.replaceAll('km', '').trim(),
      'estimated_time': fare.estimatedDuration.replaceAll('min', '').trim(),
      'estimated_fare': fare.estimatedFare.toString(),
      'actual_fare': fare.estimatedFare.toString(),
      'note': note,
      'pickup_note': '',
      'payment_method': paymentMethod,
      'type': type,
      'bid': bid,
      'pickup_address': pickup.address,
      'destination_address': destination.address,
      'intermediate_addresses': '[]',
      'entrance': '',
      'encoded_polyline': fare.polyline,
      'zone_id': fare.zoneId,
      'area_id': fare.areaId ?? '',
      'ride_request_type': 'regular',
      'scheduled_at': '',
      'extra_estimated_fare': fare.extraEstimatedFare,
      'extra_discount_fare': fare.extraDiscountFare,
      'extra_discount_amount': fare.extraDiscountAmount,
      'extra_return_fee': fare.extraReturnFee,
      'extra_cancellation_fee': fare.extraCancellationFee,
        'extra_fare_amount': fare.extraFareAmount,
        'extra_fare_fee': fare.extraFareFee,
        'surge_multiplier': fare.surgeMultiplier ?? 0,
    };

    if (isParcel) {
      body['sender_name'] = senderName ?? '';
      body['sender_phone'] = senderPhone ?? '';
      body['sender_address'] = pickup.address;
      body['receiver_name'] = receiverName ?? '';
      body['receiver_phone'] = receiverPhone ?? '';
      body['receiver_address'] = destination.address;
      body['parcel_category_id'] = parcelCategoryId ?? fare.parcelCategoryId ?? '';
      body['weight'] = (parcelWeight ?? 1).toString();
      body['payer'] = payer;
      body['return_fee'] = fare.returnFee;
      body['cancellation_fee'] = fare.cancellationFee;
    } else {
      body['vehicle_category_id'] = fare.vehicleCategoryId;
    }

    final response = await _api.post(
      ApiConstants.customerRideCreate,
      body: body,
    );

    final tripId = response.data?['data']?['id']?.toString();
    if (tripId == null || tripId.isEmpty) {
      throw Exception(response.message ?? 'Falha ao criar corrida');
    }
    return tripId;
  }
}
