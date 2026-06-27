import 'package:go_router/go_router.dart';

import '../app_state.dart';
import '../screens/documents_screen.dart';
import '../screens/earnings_screen.dart';
import '../screens/home_screen.dart';
import '../screens/login_screen.dart';
import '../screens/plans_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/trip_chat_screen_wrapper.dart';
import '../screens/register_screen.dart';
import '../screens/splash_screen.dart';
import '../screens/trip_detail_screen.dart';
import '../screens/wallet_screen.dart';

class AppRouter {
  static GoRouter create(AppState appState) {
    return GoRouter(
      initialLocation: '/splash',
      refreshListenable: appState,
      redirect: (context, state) {
        final loggedIn = appState.isLoggedIn;
        final path = state.matchedLocation;
        final isAuthRoute = path == '/login' || path == '/register';
        final isSplash = path == '/splash';

        if (isSplash) return null;
        if (!loggedIn && !isAuthRoute) return '/login';
        if (loggedIn && isAuthRoute) return '/home';
        return null;
      },
      routes: [
        GoRoute(path: '/splash', builder: (_, __) => const SplashScreen()),
        GoRoute(path: '/login', builder: (_, __) => const LoginScreen()),
        GoRoute(path: '/register', builder: (_, __) => const RegisterScreen()),
        GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
        GoRoute(path: '/earnings', builder: (_, __) => const EarningsScreen()),
        GoRoute(path: '/wallet', builder: (_, __) => const WalletScreen()),
        GoRoute(path: '/plans', builder: (_, __) => const PlansScreen()),
        GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
        GoRoute(path: '/documents', builder: (_, __) => const DocumentsScreen()),
        GoRoute(
          path: '/trip/:id',
          builder: (_, state) => TripDetailScreen(tripId: state.pathParameters['id']!),
        ),
        GoRoute(
          path: '/trip/:id/chat',
          builder: (_, state) => TripChatScreenWrapper(tripId: state.pathParameters['id']!),
        ),
      ],
    );
  }
}
