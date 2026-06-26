import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/theme/theme_controller.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';
import 'package:shared_preferences/shared_preferences.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('ThemeController', () {
    test('persists dark theme preference', () async {
      SharedPreferences.setMockInitialValues({});
      final prefs = await SharedPreferences.getInstance();
      final controller = ThemeController(sharedPreferences: prefs);

      expect(controller.darkTheme, isFalse);

      controller.changeThemeSetting(true);
      expect(controller.darkTheme, isTrue);
      expect(prefs.getBool(AppConstants.theme), isTrue);

      controller.changeThemeSetting(false);
      expect(controller.darkTheme, isFalse);
      expect(prefs.getBool(AppConstants.theme), isFalse);
    });
  });
}
