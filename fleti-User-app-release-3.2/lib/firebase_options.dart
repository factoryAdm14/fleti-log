import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';

/// Firebase config for `user.fleti.com` (project `fleti-e64b9`).
/// Values from `android/app/google-services.json` / `ios/Runner/GoogleService-Info.plist`.
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError('Web Firebase is not configured for the User app.');
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return android;
      case TargetPlatform.iOS:
        return ios;
      default:
        throw UnsupportedError(
          'DefaultFirebaseOptions are not supported for this platform.',
        );
    }
  }

  static const FirebaseOptions android = FirebaseOptions(
    apiKey: 'AIzaSyDJndkYZUvH2_uLZyuk9Z8IrtUq5FvHe7Y',
    appId: '1:28909783252:android:2514b42882e155c4955341',
    messagingSenderId: '28909783252',
    projectId: 'fleti-e64b9',
    storageBucket: 'fleti-e64b9.firebasestorage.app',
    databaseURL: 'https://fleti-e64b9-default-rtdb.firebaseio.com',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyDQ0iUdkwypN6MkfZRKC3Ai_fZQnEi-K_w',
    appId: '1:28909783252:ios:245045a4d6cb35d6955341',
    messagingSenderId: '28909783252',
    projectId: 'fleti-e64b9',
    storageBucket: 'fleti-e64b9.firebasestorage.app',
    databaseURL: 'https://fleti-e64b9-default-rtdb.firebaseio.com',
    iosBundleId: 'user.fleti.com',
  );
}
