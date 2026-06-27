import 'package:flutter/material.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:shared_flutter/shared_flutter.dart';

class TripMap extends StatelessWidget {
  const TripMap({
    super.key,
    required this.pickup,
    required this.destination,
    this.mapsReady = false,
    this.height = 280,
    this.showMyLocation = true,
  });

  final GeoPoint? pickup;
  final GeoPoint? destination;
  final bool mapsReady;
  final double height;
  final bool showMyLocation;

  @override
  Widget build(BuildContext context) {
    if (!mapsReady) {
      return ModernCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            const Icon(Icons.map_outlined, size: 40, color: FletiColors.textMuted),
            const SizedBox(height: 8),
            const Text('Mapa indisponível — configure a chave Google Maps no admin.'),
            if (pickup != null) Text('Origem: ${pickup!.address}', textAlign: TextAlign.center),
            if (destination != null) Text('Destino: ${destination!.address}', textAlign: TextAlign.center),
          ],
        ),
      );
    }

    final center = pickup ?? destination ?? GeoLocationService.defaultPoint;
    final markers = <Marker>{
      if (pickup != null)
        Marker(
          markerId: const MarkerId('pickup'),
          position: LatLng(pickup!.latitude, pickup!.longitude),
          infoWindow: InfoWindow(title: 'Origem', snippet: pickup!.address),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueGreen),
        ),
      if (destination != null)
        Marker(
          markerId: const MarkerId('destination'),
          position: LatLng(destination!.latitude, destination!.longitude),
          infoWindow: InfoWindow(title: 'Destino', snippet: destination!.address),
          icon: BitmapDescriptor.defaultMarkerWithHue(BitmapDescriptor.hueRed),
        ),
    };

    return ClipRRect(
      borderRadius: BorderRadius.circular(12),
      child: SizedBox(
        height: height,
        child: GoogleMap(
          initialCameraPosition: CameraPosition(
            target: LatLng(center.latitude, center.longitude),
            zoom: 13,
          ),
          markers: markers,
          myLocationButtonEnabled: showMyLocation,
          myLocationEnabled: showMyLocation,
          zoomControlsEnabled: true,
        ),
      ),
    );
  }
}

String googleMapsDirectionsUrl({
  required GeoPoint destination,
  GeoPoint? origin,
}) {
  final dest = '${destination.latitude},${destination.longitude}';
  if (origin != null) {
    final orig = '${origin.latitude},${origin.longitude}';
    return 'https://www.google.com/maps/dir/?api=1&origin=$orig&destination=$dest&travelmode=driving';
  }
  return 'https://www.google.com/maps/dir/?api=1&destination=$dest&travelmode=driving';
}
