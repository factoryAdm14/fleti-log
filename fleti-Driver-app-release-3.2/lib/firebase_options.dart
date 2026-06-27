import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';

/// Firebase config for `com.sixamtech.hexariderider` (project `drivevalley-fdb7f`).
/// Keeps legacy Play Store package; values from existing Android/iOS Firebase setup.
class DefaultFirebaseOptions {
  static FirebaseOptions get currentPlatform {
    if (kIsWeb) {
      throw UnsupportedError('Web Firebase is not configured for the Driver app.');
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
    apiKey: 'AIzaSyCFGqSEiWMItei_AFIUgdM53PWrvyGmjFY',
    appId: '1:76471554747:android:28346318a6d400326d0f9e',
    messagingSenderId: '76471554747',
    projectId: 'drivevalley-fdb7f',
    storageBucket: 'drivevalley-fdb7f.firebasestorage.app',
  );

  static const FirebaseOptions ios = FirebaseOptions(
    apiKey: 'AIzaSyDZU74bMiz_zWDfPgWAiKvnu9z5P8HNWwA',
    appId: '1:76471554747:ios:adbcda7af9c3a5ae6d0f9e',
    messagingSenderId: '76471554747',
    projectId: 'drivevalley-fdb7f',
    storageBucket: 'drivevalley-fdb7f.firebasestorage.app',
    iosBundleId: 'com.sixamtech.hexariderider',
  );
}
