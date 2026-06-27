import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import 'package:shared_flutter/shared_flutter.dart';

import 'app_state.dart';
import 'router/app_router.dart';
import 'utils/maps_loader.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  final storage = await StorageService.create();
  final api = ApiService(token: storage.token, locale: storage.locale, zoneId: storage.zoneId);
  final auth = AuthService(api, storage, AppRole.customer);
  final configService = ConfigService(api, AppRole.customer);
  final locationService = LocationService(api);
  final rideService = RideService(api);
  final tripService = CustomerTripService(api);
  final paymentService = PaymentService(api);
  final walletService = CustomerWalletService(api, paymentService);
  final pusher = FletiPusherService();
  final chatService = ChatService(api, AppRole.customer);
  final reviewService = ReviewService(api, AppRole.customer);
  final geoLocation = GeoLocationService(locationService);

  final appState = AppState(
    auth: auth,
    configService: configService,
    locationService: locationService,
    rideService: rideService,
    tripService: tripService,
    paymentService: paymentService,
    walletService: walletService,
    chatService: chatService,
    reviewService: reviewService,
    pusher: pusher,
    geoLocation: geoLocation,
    storage: storage,
    api: api,
  );

  await appState.bootstrap();
  await loadGoogleMaps(appState.config?.mapApiKey);

  final router = AppRouter.create(appState);

  runApp(
    MultiProvider(
      providers: [
        Provider.value(value: storage),
        Provider.value(value: api),
        Provider.value(value: locationService),
        Provider.value(value: rideService),
        Provider.value(value: tripService),
        Provider.value(value: paymentService),
        Provider.value(value: walletService),
        ChangeNotifierProvider.value(value: pusher),
        Provider.value(value: geoLocation),
        ChangeNotifierProvider.value(value: appState),
      ],
      child: ClientWebApp(router: router),
    ),
  );
}

class ClientWebApp extends StatelessWidget {
  const ClientWebApp({super.key, required this.router});

  final GoRouter router;

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'Fleti Cliente',
      debugShowCheckedModeBanner: false,
      theme: FletiTheme.light(),
      routerConfig: router,
    );
  }
}
