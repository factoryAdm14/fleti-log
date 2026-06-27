class GeoPoint {
  const GeoPoint(this.latitude, this.longitude, {this.address = ''});

  final double latitude;
  final double longitude;
  final String address;

  String get coordinatesJson => '[$latitude,$longitude]';
}
