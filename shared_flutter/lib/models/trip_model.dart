import 'geo_point.dart';

class TripPerson {
  TripPerson({
    required this.id,
    required this.firstName,
    required this.lastName,
    this.phone,
    this.profileImage,
  });

  factory TripPerson.fromJson(Map<String, dynamic> json) {
    return TripPerson(
      id: '${json['id'] ?? ''}',
      firstName: '${json['first_name'] ?? ''}',
      lastName: '${json['last_name'] ?? ''}',
      phone: json['phone']?.toString(),
      profileImage: json['profile_image']?.toString(),
    );
  }

  final String id;
  final String firstName;
  final String lastName;
  final String? phone;
  final String? profileImage;

  String get fullName => '$firstName $lastName'.trim();
}

class TripModel {
  TripModel({
    required this.id,
    required this.refId,
    required this.currentStatus,
    required this.paymentStatus,
    required this.pickupAddress,
    required this.destinationAddress,
    this.pickup,
    this.destination,
    this.driver,
    this.customer,
    this.estimatedFare,
    this.estimatedTime,
    this.estimatedDistance,
    this.paymentMethod,
    this.createdAt,
    this.vehicleCategoryType,
  });

  factory TripModel.fromJson(Map<String, dynamic> json) {
    return TripModel(
      id: '${json['id'] ?? ''}',
      refId: '${json['ref_id'] ?? ''}',
      currentStatus: '${json['current_status'] ?? ''}',
      paymentStatus: '${json['payment_status'] ?? ''}',
      pickupAddress: '${json['pickup_address'] ?? ''}',
      destinationAddress: '${json['destination_address'] ?? ''}',
      pickup: _pointFromCoords(json['pickup_coordinates'], '${json['pickup_address'] ?? ''}'),
      destination: _pointFromCoords(json['destination_coordinates'], '${json['destination_address'] ?? ''}'),
      driver: json['driver'] is Map ? TripPerson.fromJson(json['driver'] as Map<String, dynamic>) : null,
      customer: json['customer'] is Map ? TripPerson.fromJson(json['customer'] as Map<String, dynamic>) : null,
      estimatedFare: double.tryParse('${json['estimated_fare']}'),
      estimatedTime: json['estimated_time']?.toString(),
      estimatedDistance: double.tryParse('${json['estimated_distance']}'),
      paymentMethod: json['payment_method']?.toString(),
      createdAt: json['created_at']?.toString(),
      vehicleCategoryType: json['vehicle_category'] is Map
          ? json['vehicle_category']['name']?.toString()
          : null,
    );
  }

  final String id;
  final String refId;
  final String currentStatus;
  final String paymentStatus;
  final String pickupAddress;
  final String destinationAddress;
  final GeoPoint? pickup;
  final GeoPoint? destination;
  final TripPerson? driver;
  final TripPerson? customer;
  final double? estimatedFare;
  final String? estimatedTime;
  final double? estimatedDistance;
  final String? paymentMethod;
  final String? createdAt;
  final String? vehicleCategoryType;

  bool get isActive => !{'completed', 'cancelled'}.contains(currentStatus);
  bool get isCompleted => currentStatus == 'completed';
  bool get isCancelled => currentStatus == 'cancelled';
  bool get needsPayment => isCompleted && paymentStatus != 'paid';

  static GeoPoint? _pointFromCoords(dynamic raw, String address) {
    if (raw is! Map) return null;
    final coords = raw['coordinates'];
    if (coords is! List || coords.length < 2) return null;
    final lng = double.tryParse('${coords[0]}');
    final lat = double.tryParse('${coords[1]}');
    if (lat == null || lng == null) return null;
    return GeoPoint(lat, lng, address: address);
  }
}

String tripStatusLabel(String status) {
  return switch (status) {
    'pending' => 'Aguardando motorista',
    'accepted' => 'Motorista aceitou',
    'out_for_pickup' => 'A caminho',
    'ongoing' => 'Em andamento',
    'completed' => 'Concluída',
    'cancelled' => 'Cancelada',
    _ => status,
  };
}
