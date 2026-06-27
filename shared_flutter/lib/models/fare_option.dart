class FareOption {
  FareOption({
    required this.vehicleCategoryId,
    required this.vehicleCategoryType,
    required this.estimatedFare,
    required this.estimatedDistance,
    required this.estimatedDuration,
    required this.zoneId,
    required this.polyline,
    this.areaId,
    this.extraEstimatedFare = 0,
    this.extraDiscountFare = 0,
    this.extraDiscountAmount = 0,
    this.extraReturnFee = 0,
    this.extraCancellationFee = 0,
    this.extraFareAmount = 0,
    this.extraFareFee = 0,
    this.surgeMultiplier,
    this.returnFee = 0,
    this.cancellationFee = 0,
    this.parcelCategoryId,
  });

  factory FareOption.fromJson(Map<String, dynamic> json) {
    return FareOption(
      vehicleCategoryId: '${json['vehicle_category_id'] ?? json['id'] ?? ''}',
      vehicleCategoryType: '${json['vehicle_category_type'] ?? json['request type'] ?? 'Entrega'}',
      estimatedFare: double.tryParse('${json['estimated_fare']}') ?? 0,
      estimatedDistance: '${json['estimated_distance'] ?? ''}',
      estimatedDuration: '${json['estimated_duration'] ?? ''}',
      zoneId: '${json['zone_id'] ?? ''}',
      polyline: '${json['encoded_polyline'] ?? ''}',
      areaId: json['area_id']?.toString(),
      extraEstimatedFare: double.tryParse('${json['extra_estimated_fare']}') ?? 0,
      extraDiscountFare: double.tryParse('${json['extra_discount_fare']}') ?? 0,
      extraDiscountAmount: double.tryParse('${json['extra_discount_amount']}') ?? 0,
      extraReturnFee: double.tryParse('${json['extra_return_fee']}') ?? 0,
      extraCancellationFee: double.tryParse('${json['extra_cancellation_fee']}') ?? 0,
      extraFareAmount: double.tryParse('${json['extra_fare_amount']}') ?? 0,
      extraFareFee: double.tryParse('${json['extra_fare_fee']}') ?? 0,
      surgeMultiplier: double.tryParse('${json['surge_multiplier']}'),
      returnFee: double.tryParse('${json['return_fee']}') ?? 0,
      cancellationFee: double.tryParse('${json['cancellation_fee']}') ?? 0,
      parcelCategoryId: json['parcel_category_id']?.toString(),
    );
  }

  final String vehicleCategoryId;
  final String vehicleCategoryType;
  final double estimatedFare;
  final String estimatedDistance;
  final String estimatedDuration;
  final String zoneId;
  final String polyline;
  final String? areaId;
  final double extraEstimatedFare;
  final double extraDiscountFare;
  final double extraDiscountAmount;
  final double extraReturnFee;
  final double extraCancellationFee;
  final double extraFareAmount;
  final double extraFareFee;
  final double? surgeMultiplier;
  final double returnFee;
  final double cancellationFee;
  final String? parcelCategoryId;
}
