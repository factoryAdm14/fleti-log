import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ModernTextField extends StatelessWidget {
  final TextEditingController? controller;
  final String? hintText;
  final String? labelText;
  final Widget? prefixIcon;
  final Widget? suffixIcon;
  final bool obscureText;
  final TextInputType keyboardType;
  final int maxLines;
  final ValueChanged<String>? onChanged;
  final String? Function(String?)? validator;

  const ModernTextField({
    super.key,
    this.controller,
    this.hintText,
    this.labelText,
    this.prefixIcon,
    this.suffixIcon,
    this.obscureText = false,
    this.keyboardType = TextInputType.text,
    this.maxLines = 1,
    this.onChanged,
    this.validator,
  });

  @override
  Widget build(BuildContext context) {
    return TextFormField(
      controller: controller,
      obscureText: obscureText,
      keyboardType: keyboardType,
      maxLines: maxLines,
      onChanged: onChanged,
      validator: validator,
      style: textRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
      decoration: InputDecoration(
        labelText: labelText,
        hintText: hintText,
        prefixIcon: prefixIcon,
        suffixIcon: suffixIcon,
        filled: true,
        fillColor: FletiDesignTokens.surface(context),
        contentPadding: const EdgeInsets.symmetric(
          horizontal: Dimensions.paddingSizeDefault,
          vertical: Dimensions.paddingSizeSmall,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(FletiDesignTokens.radiusMd),
          borderSide: BorderSide(color: FletiDesignTokens.border(context)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(FletiDesignTokens.radiusMd),
          borderSide: BorderSide(color: FletiDesignTokens.border(context)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(FletiDesignTokens.radiusMd),
          borderSide: BorderSide(
            color: FletiDesignTokens.primary(context),
            width: 1.5,
          ),
        ),
      ),
    );
  }
}
