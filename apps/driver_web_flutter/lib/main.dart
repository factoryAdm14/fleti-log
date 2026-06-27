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
  final auth = AuthService(api, storage, AppRole.driver);
  final configService = ConfigService(api, AppRole.driver);
  final locationService = LocationService(api);
  final driverRideService = DriverRideService(api);
  final pusher = FletiPusherService();
  final walletService = DriverWalletService(api);
  final planService = DriverPlanService(api);
  final chatService = ChatService(api, AppRole.driver);
  final geoLocation = GeoLocationService(locationService);

  final appState = AppState(
    auth: auth,
    configService: configService,
    driverRideService: driverRideService,
    pusher: pusher,
    walletService: walletService,
    planService: planService,
    chatService: chatService,
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
        Provider.value(value: driverRideService),
        Provider.value(value: walletService),
        Provider.value(value: planService),
        ChangeNotifierProvider.value(value: pusher),
        ChangeNotifierProvider.value(value: appState),
      ],
      child: DriverWebApp(router: router),
    ),
  );
}

class DriverWebApp extends StatelessWidget {
  const DriverWebApp({super.key, required this.router});

  final GoRouter router;

  @override
  Widget build(BuildContext context) {
    return MaterialApp.router(
      title: 'Fleti Motorista',
      debugShowCheckedModeBanner: false,
      theme: FletiTheme.light(),
      routerConfig: router,
    );
  }
}
