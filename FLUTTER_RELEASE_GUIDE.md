# Flutter Release Guide — R26-01

Release **Fleti User** and **Fleti Driver** v3.2.0 for Android (APK + AAB).

## Prerequisites

- Flutter 3.38+ (`flutter doctor`)
- Android SDK (compileSdk 36)
- Java 11+
- Play Console access (for AAB upload)

## Versioning

| App | `pubspec.yaml` | `applicationId` / bundle |
|-----|----------------|--------------------------|
| User | `3.2.0+32` | `user.fleti.com` |
| Driver | `3.2.0+32` | `com.sixamtech.hexariderider` (legacy Play listing) |

`versionCode` = `32`, `versionName` = `3.2.0`.

## Production signing (Play Store)

1. Generate keystore (once per app or shared):

```bash
keytool -genkey -v -keystore upload-keystore.jks \
  -keyalg RSA -keysize 2048 -validity 10000 -alias upload
```

2. Copy `android/key.properties.example` → `android/key.properties` in each app.
3. Place `upload-keystore.jks` in `android/` (gitignored).
4. Release builds auto-use the release keystore when `key.properties` exists; otherwise debug signing (sideload only).

## Firebase

| App | Project | Package / bundle |
|-----|---------|------------------|
| User | `fleti-e64b9` | `user.fleti.com` |
| Driver | `drivevalley-fdb7f` | `com.sixamtech.hexariderider` |

Config lives in `lib/firebase_options.dart` per app. Admin FCM must target the same Firebase project configured in the panel.

## Build (automated)

```bash
chmod +x scripts/build_flutter_release.sh
./scripts/build_flutter_release.sh all
```

Outputs:

```
build/flutter-release/
  fleti-user-3.2.0.apk
  fleti-user-3.2.0.aab
  fleti-driver-3.2.0.apk
  fleti-driver-3.2.0.aab
```

Targets: `all` | `user` | `driver` | `apk` | `aab`

## Build (manual)

### User

```bash
cd fleti-User-app-release-3.2
flutter pub get && flutter test && flutter analyze
flutter build apk --release
flutter build appbundle --release
```

### Driver

```bash
cd fleti-Driver-app-release-3.2
flutter pub get && flutter test && flutter analyze
flutter build apk --release
flutter build appbundle --release
```

## Pre-upload checklist

- [ ] Login / OTP / registro (User e Driver)
- [ ] Solicitar corrida + aceitar (fluxo completo)
- [ ] Carteira e PIX (se ativo no Admin)
- [ ] Dark mode nas telas principais
- [ ] Push notification (FCM token registrado)
- [ ] Deep links: `https://fleti.com.br` (Android App Links + iOS universal links)
- [ ] `baseUrl` = `https://fleti.com.br` em `app_constants.dart`

## Play Console upload

1. **Internal testing** primeiro (AAB).
2. User: package `user.fleti.com`
3. Driver: package `com.sixamtech.hexariderider` (atualização da listing existente)
4. Release notes: tema moderno, dark mode, versão 3.2, API `fleti.com.br`

## iOS (fora do escopo imediato R26-01)

```bash
flutter build ipa --release
```

Requer certificados Apple + provisioning profiles. Resolver plist duplicado no Driver (`ios/GoogleService-Info.plist` vs `ios/Runner/GoogleService-Info.plist`).

## Troubleshooting

| Problema | Solução |
|----------|---------|
| FCM não chega (User) | Confirmar projeto `fleti-e64b9` no Admin |
| FCM não chega (Driver) | Confirmar projeto `drivevalley-fdb7f` no Admin |
| `google-services.json` mismatch | User usa plugin; Driver usa `firebase_options.dart` apenas |
| Build lento | `flutter clean` + rebuild |
