#!/usr/bin/env bash
# R26-01 — Build Flutter release artifacts (User + Driver).
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
USER_APP="$ROOT/fleti-User-app-release-3.2"
DRIVER_APP="$ROOT/fleti-Driver-app-release-3.2"
OUT="$ROOT/build/flutter-release"
TARGET="${1:-all}" # all | user | driver | apk | aab

mkdir -p "$OUT"

build_user() {
  echo "==> User app: pub get, test, analyze"
  cd "$USER_APP"
  flutter pub get
  flutter test
  flutter analyze --no-fatal-infos

  echo "==> User app: APK"
  flutter build apk --release
  cp build/app/outputs/flutter-apk/app-release.apk "$OUT/fleti-user-3.2.0.apk"

  echo "==> User app: AAB (Play Store)"
  flutter build appbundle --release
  cp build/app/outputs/bundle/release/app-release.aab "$OUT/fleti-user-3.2.0.aab"
}

build_driver() {
  echo "==> Driver app: pub get, test, analyze"
  cd "$DRIVER_APP"
  flutter pub get
  flutter test
  flutter analyze --no-fatal-infos

  echo "==> Driver app: APK"
  flutter build apk --release
  cp build/app/outputs/flutter-apk/app-release.apk "$OUT/fleti-driver-3.2.0.apk"

  echo "==> Driver app: AAB (Play Store)"
  flutter build appbundle --release
  cp build/app/outputs/bundle/release/app-release.aab "$OUT/fleti-driver-3.2.0.aab"
}

case "$TARGET" in
  user) build_user ;;
  driver) build_driver ;;
  apk)
    cd "$USER_APP" && flutter pub get && flutter build apk --release
    cp "$USER_APP/build/app/outputs/flutter-apk/app-release.apk" "$OUT/fleti-user-3.2.0.apk"
    cd "$DRIVER_APP" && flutter pub get && flutter build apk --release
    cp "$DRIVER_APP/build/app/outputs/flutter-apk/app-release.apk" "$OUT/fleti-driver-3.2.0.apk"
    ;;
  aab)
    cd "$USER_APP" && flutter pub get && flutter build appbundle --release
    cp "$USER_APP/build/app/outputs/bundle/release/app-release.aab" "$OUT/fleti-user-3.2.0.aab"
    cd "$DRIVER_APP" && flutter pub get && flutter build appbundle --release
    cp "$DRIVER_APP/build/app/outputs/bundle/release/app-release.aab" "$OUT/fleti-driver-3.2.0.aab"
    ;;
  all)
    build_user
    build_driver
    ;;
  *)
    echo "Usage: $0 [all|user|driver|apk|aab]"
    exit 1
    ;;
esac

echo ""
echo "Artifacts in: $OUT"
ls -lh "$OUT"
