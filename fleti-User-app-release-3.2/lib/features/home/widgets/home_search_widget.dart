import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/home/widgets/voice_search_dialog.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/util/styles.dart';
import 'package:ride_sharing_user_app/features/set_destination/screens/set_destination_screen.dart';

class HomeSearchWidget extends StatelessWidget {
  const HomeSearchWidget({super.key});

  InputBorder _border(BuildContext context) => OutlineInputBorder(
        borderRadius: BorderRadius.circular(FletiDesignTokens.radiusLg),
        borderSide: BorderSide(color: FletiDesignTokens.border(context)),
      );

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: Dimensions.searchBarSize,
      child: TextField(
        style: textRegular.copyWith(color: Theme.of(context).textTheme.bodyMedium!.color!.withValues(alpha:0.8)),
        cursorColor: Theme.of(context).hintColor,
        autofocus: false,
        readOnly: true,
        textAlignVertical: TextAlignVertical.center,
        textInputAction: TextInputAction.search,
        decoration: InputDecoration(
          filled: true,
          fillColor: Theme.of(context).cardColor,
          contentPadding: const EdgeInsets.symmetric(
            horizontal :Dimensions.paddingSizeDefault,
            vertical:Dimensions.paddingSizeExtraSmall,
          ),
          border: _border(context),
          enabledBorder: _border(context),
          errorBorder: _border(context),
          focusedErrorBorder: _border(context),
          focusedBorder: OutlineInputBorder(
            borderRadius: BorderRadius.circular(FletiDesignTokens.radiusLg),
            borderSide: BorderSide(color: Theme.of(context).colorScheme.primary, width: 1.5),
          ),
          disabledBorder: _border(context),
          isDense: true,
          hintText: 'where_to_go'.tr,
          hintStyle: textRegular.copyWith(
              color: Theme.of(context).textTheme.bodyMedium!.color!.withValues(alpha:0.5),
          ),
          suffixIcon: IconButton(
            color: Theme.of(context).hintColor,
            onPressed: () {
              Get.dialog(const VoiceSearchDialog(),barrierDismissible: false);
            },
            icon:Image.asset(
              Images.microPhoneIcon,
              color: Get.isDarkMode? Theme.of(context).hintColor : null ,
              height: 20, width: 20,
            ),
          ),
          prefixIcon: IconButton(
            color: Theme.of(context).hintColor,
            onPressed: () => Get.to(() => const SetDestinationScreen()),
            icon:Image.asset(
              Images.homeSearchIcon,
              color: Get.isDarkMode? Theme.of(context).hintColor : null ,
              height: 20, width: 20,
            ),
          ),
        ),
        onTap: () => Get.to(() => const SetDestinationScreen()),
      ),
    );
  }
}
