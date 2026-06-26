/// Fleti Enterprise v4 — Flutter performance defaults (FASE 012).
class FletiPerformanceConfig {
  FletiPerformanceConfig._();

  /// Extra pixels kept off-screen for horizontal/vertical lists.
  static const double listCacheExtent = 320;

  /// Default decoded image cache size when layout size is unknown.
  static const int defaultImageMemCachePx = 400;

  /// Max decoded image dimension to limit memory on large URLs.
  static const int maxImageMemCachePx = 1200;

  /// Minimum movement before GPS stream fires again (meters).
  static const int locationDistanceFilterMeters = 10;

  /// Stream accuracy — balanced for map tracking (saves battery vs `high`).
  static const String locationStreamAccuracy = 'medium';
}
