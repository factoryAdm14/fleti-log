import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

void main() {
  test('driver app version is 3.2', () {
    expect(AppConstants.appVersion, 3.2);
  });

  test('design token spacing is positive', () {
    expect(FletiDesignTokens.spaceMd, greaterThan(0));
    expect(FletiDesignTokens.borderWidth, greaterThan(0));
  });
}
