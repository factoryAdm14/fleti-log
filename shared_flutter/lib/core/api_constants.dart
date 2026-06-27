import 'app_role.dart';

class ApiConstants {
  static const String baseUrl = 'https://fleti.com.br';
  static const String defaultLocale = 'pt';

  static String customerConfig() => '$baseUrl/api/customer/configuration';
  static String driverConfig() => '$baseUrl/api/driver/configuration';

  static String loginUri(AppRole role) => role == AppRole.customer
      ? '$baseUrl/api/customer/auth/login'
      : '$baseUrl/api/driver/auth/login';

  static String registrationUri(AppRole role) => role == AppRole.customer
      ? '$baseUrl/api/customer/auth/registration'
      : '$baseUrl/api/driver/auth/registration';

  static String profileUri(AppRole role) => role == AppRole.customer
      ? '$baseUrl/api/customer/info'
      : '$baseUrl/api/driver/info';

  static String logoutUri() => '$baseUrl/api/user/logout';

  static String broadcastingAuth(String websocketHost) =>
      'https://$websocketHost/broadcasting/auth';

  static const String customerZone = '$baseUrl/api/customer/config/get-zone-id';
  static const String customerGeocode = '$baseUrl/api/customer/config/geocode-api';
  static const String customerPlaceSearch =
      '$baseUrl/api/customer/config/place-api-autocomplete';
  static const String customerPlaceDetails =
      '$baseUrl/api/customer/config/place-api-details';
  static const String customerEstimatedFare =
      '$baseUrl/api/customer/ride/get-estimated-fare';
  static const String customerRideCreate = '$baseUrl/api/customer/ride/create';

  static String customerRegistration() => '$baseUrl/api/customer/auth/registration';
  static String customerSendOtp() => '$baseUrl/api/customer/auth/send-otp';
  static String customerResetPassword() => '$baseUrl/api/customer/auth/reset-password';

  static const String customerTripDetails = '$baseUrl/api/customer/ride/details/';
  static String customerTripList({
    int offset = 1,
    String status = '',
    String filter = '',
  }) =>
      '$baseUrl/api/customer/ride/list?type=ride_request&limit=20&offset=$offset&filter=$filter&start=&end=&status=$status';

  static String customerCancelTrip(String id) => '$baseUrl/api/customer/ride/update-status/$id';

  static const String driverOnlineStatus = '$baseUrl/api/driver/update-online-status';
  static String driverPendingRides({int limit = 20, int offset = 1}) =>
      '$baseUrl/api/driver/ride/pending-ride-list?limit=$limit&offset=$offset';
  static const String driverTripAction = '$baseUrl/api/driver/ride/trip-action';
  static const String driverTripDetails = '$baseUrl/api/driver/ride/details/';
  static String driverUpdateStatus = '$baseUrl/api/driver/ride/update-status';
  static const String driverStoreLocation = '$baseUrl/api/user/store-live-location';
}
