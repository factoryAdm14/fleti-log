import 'package:go_router/go_router.dart';

import '../app_state.dart';
import '../screens/forgot_password_screen.dart';
import '../screens/history_screen.dart';
import '../screens/home_screen.dart';
import '../screens/login_screen.dart';
import '../screens/profile_screen.dart';
import '../screens/payment_screen.dart';
import '../screens/ride_request_screen.dart';
import '../screens/register_screen.dart';
import '../screens/review_screen.dart';
import '../screens/ride_tracking_screen.dart';
import '../screens/trip_chat_screen_wrapper.dart';
import '../screens/wallet_screen.dart';
import '../screens/splash_screen.dart';

class AppRouter {
  static GoRouter create(AppState appState) {
    return GoRouter(
      initialLocation: '/splash',
      refreshListenable: appState,
      redirect: (context, state) {
        final loggedIn = appState.isLoggedIn;
        final path = state.matchedLocation;
        final isAuthRoute = path == '/login' || path == '/register' || path == '/forgot-password';
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
        GoRoute(path: '/forgot-password', builder: (_, __) => const ForgotPasswordScreen()),
        GoRoute(path: '/home', builder: (_, __) => const HomeScreen()),
        GoRoute(path: '/ride/new', builder: (_, state) => RideRequestScreen(serviceType: state.uri.queryParameters['type'] ?? 'ride')),
        GoRoute(path: '/history', builder: (_, __) => const HistoryScreen()),
        GoRoute(path: '/profile', builder: (_, __) => const ProfileScreen()),
        GoRoute(path: '/wallet', builder: (_, __) => const WalletScreen()),
        GoRoute(path: '/ride/:id', builder: (_, state) => RideTrackingScreen(tripId: state.pathParameters['id']!)),
        GoRoute(
          path: '/ride/:id/chat',
          builder: (_, state) => TripChatScreenWrapper(tripId: state.pathParameters['id']!),
        ),
        GoRoute(
          path: '/ride/:id/review',
          builder: (_, state) => ReviewScreen(tripId: state.pathParameters['id']!),
        ),
        GoRoute(
          path: '/ride/:id/payment',
          builder: (_, state) => PaymentScreen(tripId: state.pathParameters['id']!),
        ),
      ],
    );
  }
}
