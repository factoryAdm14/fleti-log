import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/theme/fleti_design_tokens.dart';

class ModernContainer extends StatelessWidget {
  final Widget child;
  final EdgeInsetsGeometry? padding;
  final EdgeInsetsGeometry? margin;
  final Color? color;
  final double? width;
  final double? height;
  final AlignmentGeometry alignment;
  final double borderRadius;

  const ModernContainer({
    super.key,
    required this.child,
    this.padding,
    this.margin,
    this.color,
    this.width,
    this.height,
    this.alignment = Alignment.center,
    this.borderRadius = FletiDesignTokens.radiusMd,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: width,
      height: height,
      margin: margin,
      padding: padding ?? const EdgeInsets.all(FletiDesignTokens.spaceMd),
      alignment: alignment,
      decoration: BoxDecoration(
        color: color ?? FletiDesignTokens.surface(context),
        borderRadius: BorderRadius.circular(borderRadius),
        border: Border.all(color: FletiDesignTokens.border(context)),
      ),
      child: child,
    );
  }
}
