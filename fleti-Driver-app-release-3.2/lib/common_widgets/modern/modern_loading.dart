import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ModernLoading extends StatelessWidget {
  final String? message;
  final bool overlay;

  const ModernLoading({
    super.key,
    this.message,
    this.overlay = false,
  });

  @override
  Widget build(BuildContext context) {
    final content = Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        SizedBox(
          width: 32,
          height: 32,
          child: CircularProgressIndicator(
            strokeWidth: 2.5,
            color: FletiDesignTokens.primary(context),
          ),
        ),
        if (message != null) ...[
          const SizedBox(height: Dimensions.paddingSizeDefault),
          Text(
            message!,
            style: textRegular.copyWith(
              fontSize: Dimensions.fontSizeDefault,
              color: FletiDesignTokens.onSurfaceMuted(context),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ],
    );

    if (!overlay) {
      return Center(child: content);
    }

    return ColoredBox(
      color: Colors.black.withValues(alpha: 0.25),
      child: Center(child: content),
    );
  }
}
