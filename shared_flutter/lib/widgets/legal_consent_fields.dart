import 'package:flutter/gestures.dart';
import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../theme/fleti_theme.dart';

class LegalConsentFields extends StatelessWidget {
  const LegalConsentFields({
    super.key,
    required this.termsAccepted,
    required this.privacyAccepted,
    required this.locationAccepted,
    required this.marketingAccepted,
    required this.onTermsChanged,
    required this.onPrivacyChanged,
    required this.onLocationChanged,
    required this.onMarketingChanged,
    this.termsUrl = 'https://fleti.com.br/terms',
    this.privacyUrl = 'https://fleti.com.br/privacy',
  });

  final bool termsAccepted;
  final bool privacyAccepted;
  final bool locationAccepted;
  final bool marketingAccepted;
  final ValueChanged<bool?> onTermsChanged;
  final ValueChanged<bool?> onPrivacyChanged;
  final ValueChanged<bool?> onLocationChanged;
  final ValueChanged<bool?> onMarketingChanged;
  final String termsUrl;
  final String privacyUrl;

  Future<void> _open(String url) async {
    final uri = Uri.parse(url);
    if (await canLaunchUrl(uri)) {
      await launchUrl(uri, mode: LaunchMode.externalApplication);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _ConsentRow(
          value: termsAccepted,
          onChanged: onTermsChanged,
          child: _linkedLabel(
            prefix: 'Li e aceito os ',
            linkText: 'Termos de Uso',
            suffix: '.',
            onTap: () => _open(termsUrl),
          ),
        ),
        _ConsentRow(
          value: privacyAccepted,
          onChanged: onPrivacyChanged,
          child: _linkedLabel(
            prefix: 'Li e aceito a ',
            linkText: 'Política de Privacidade',
            suffix: '.',
            onTap: () => _open(privacyUrl),
          ),
        ),
        _ConsentRow(
          value: locationAccepted,
          onChanged: onLocationChanged,
          child: const Text(
            'Autorizo o uso da minha localização para funcionamento da plataforma.',
            style: TextStyle(fontSize: 13, height: 1.4),
          ),
        ),
        _ConsentRow(
          value: marketingAccepted,
          onChanged: onMarketingChanged,
          child: const Text(
            'Aceito receber comunicações promocionais. (Opcional)',
            style: TextStyle(fontSize: 13, height: 1.4, color: FletiColors.textMuted),
          ),
        ),
      ],
    );
  }

  Widget _linkedLabel({
    required String prefix,
    required String linkText,
    required String suffix,
    required VoidCallback onTap,
  }) {
    return Text.rich(
      TextSpan(
        style: const TextStyle(fontSize: 13, height: 1.4, color: FletiColors.text),
        children: [
          TextSpan(text: prefix),
          TextSpan(
            text: linkText,
            style: const TextStyle(color: FletiColors.primary, decoration: TextDecoration.underline),
            recognizer: TapGestureRecognizer()..onTap = onTap,
          ),
          TextSpan(text: suffix),
        ],
      ),
    );
  }
}

class _ConsentRow extends StatelessWidget {
  const _ConsentRow({
    required this.value,
    required this.onChanged,
    required this.child,
  });

  final bool value;
  final ValueChanged<bool?> onChanged;
  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 24,
            height: 24,
            child: Checkbox(
              value: value,
              onChanged: onChanged,
              materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
            ),
          ),
          const SizedBox(width: 8),
          Expanded(child: child),
        ],
      ),
    );
  }
}
