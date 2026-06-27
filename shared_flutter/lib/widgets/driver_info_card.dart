import 'package:flutter/material.dart';

import '../models/trip_model.dart';
import '../theme/fleti_theme.dart';
import 'modern_card.dart';
import 'status_badge.dart';

class DriverInfoCard extends StatelessWidget {
  const DriverInfoCard({super.key, required this.driver});

  final TripPerson driver;

  @override
  Widget build(BuildContext context) {
    return ModernCard(
      child: Row(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: FletiColors.border,
            child: driver.profileImage != null && driver.profileImage!.isNotEmpty
                ? ClipOval(child: Image.network(driver.profileImage!, width: 56, height: 56, fit: BoxFit.cover))
                : const Icon(Icons.person),
          ),
          const SizedBox(width: 16),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(driver.fullName, style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600)),
                if (driver.phone != null) Text(driver.phone!, style: const TextStyle(color: FletiColors.textMuted)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
