import 'package:flutter/material.dart';

import '../theme/fleti_theme.dart';
import 'primary_button.dart';

class ErrorState extends StatelessWidget {
  const ErrorState({
    super.key,
    required this.message,
    this.onRetry,
  });

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        const Icon(Icons.error_outline, color: FletiColors.error, size: 40),
        const SizedBox(height: 12),
        Text(message, textAlign: TextAlign.center),
        if (onRetry != null) ...[
          const SizedBox(height: 16),
          PrimaryButton(label: 'Tentar novamente', onPressed: onRetry),
        ],
      ],
    );
  }
}
