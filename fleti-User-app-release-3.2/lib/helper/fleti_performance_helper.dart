import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/util/fleti_performance_config.dart';

/// Helpers for Fleti performance tuning (FASE 012).
class FletiPerformanceHelper {
  FletiPerformanceHelper._();

  static int memCachePx(BuildContext context, double? logicalSize) {
    final ratio = MediaQuery.devicePixelRatioOf(context);
    final base = logicalSize ?? 200;
    return (base * ratio)
        .round()
        .clamp(64, FletiPerformanceConfig.maxImageMemCachePx);
  }
}
