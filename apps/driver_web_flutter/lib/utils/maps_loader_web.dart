// ignore: avoid_web_libraries_in_flutter
import 'dart:html' as html;

bool _loaded = false;

Future<void> loadGoogleMaps(String? apiKey) async {
  if (apiKey == null || apiKey.isEmpty || _loaded) return;
  final script = html.ScriptElement()
    ..id = 'google-maps-script'
    ..src = 'https://maps.googleapis.com/maps/api/js?key=$apiKey'
    ..async = true
    ..defer = true;
  html.document.head?.append(script);
  _loaded = true;
}
