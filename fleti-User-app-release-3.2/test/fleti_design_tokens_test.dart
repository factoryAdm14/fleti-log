import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

void main() {
  group('Fleti design tokens', () {
    test('radius scale is ordered', () {
      expect(FletiDesignTokens.radiusSm, lessThan(FletiDesignTokens.radiusMd));
      expect(FletiDesignTokens.radiusMd, lessThan(FletiDesignTokens.radiusLg));
      expect(FletiDesignTokens.radiusLg, lessThan(FletiDesignTokens.radiusXl));
    });

    test('spacing scale is ordered', () {
      expect(FletiDesignTokens.spaceXs, lessThan(FletiDesignTokens.spaceSm));
      expect(FletiDesignTokens.spaceSm, lessThan(FletiDesignTokens.spaceMd));
      expect(FletiDesignTokens.spaceMd, lessThan(FletiDesignTokens.spaceLg));
    });
  });

  group('App constants', () {
    test('app version is 3.2', () {
      expect(AppConstants.appVersion, 3.2);
    });

    test('production base url uses https', () {
      expect(AppConstants.baseUrl, startsWith('https://'));
    });
  });
}
