import 'dart:convert';

import 'package:http/http.dart' as http;

import '../core/api_exception.dart';
import '../models/api_response.dart';

class ApiService {
  ApiService({
    http.Client? client,
    String? token,
    String locale = 'pt',
    String? zoneId,
  })  : _client = client ?? http.Client(),
        _token = token,
        _locale = locale,
        _zoneId = zoneId;

  final http.Client _client;
  String? _token;
  String _locale;
  String? _zoneId;

  void updateHeaders({String? token, String? locale, String? zoneId}) {
    if (token != null) _token = token;
    if (locale != null) _locale = locale;
    if (zoneId != null) _zoneId = zoneId;
  }

  Map<String, String> get _headers {
    final headers = <String, String>{
      'Content-Type': 'application/json; charset=UTF-8',
      'Accept': 'application/json',
      'X-Localization': _locale,
    };
    if (_token != null && _token!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $_token';
    }
    if (_zoneId != null && _zoneId!.isNotEmpty) {
      headers['zoneId'] = _zoneId!;
    }
    return headers;
  }

  Future<ApiResponse<Map<String, dynamic>>> get(String uri) async {
    final response = await _client.get(Uri.parse(uri), headers: _headers);
    return _parse(response);
  }

  Future<ApiResponse<Map<String, dynamic>>> post(
    String uri, {
    Map<String, dynamic>? body,
  }) async {
    final response = await _client.post(
      Uri.parse(uri),
      headers: _headers,
      body: body == null ? null : jsonEncode(body),
    );
    return _parse(response);
  }

  Future<ApiResponse<Map<String, dynamic>>> put(
    String uri, {
    Map<String, dynamic>? body,
  }) async {
    final response = await _client.put(
      Uri.parse(uri),
      headers: _headers,
      body: body == null ? null : jsonEncode(body),
    );
    return _parse(response);
  }

  Future<ApiResponse<Map<String, dynamic>>> postMultipart(
    String uri,
    Map<String, String> fields,
  ) async {
    final request = http.MultipartRequest('POST', Uri.parse(uri));
    request.headers.addAll(_headers);
    request.fields.addAll(fields);
    final streamed = await request.send();
    final response = await http.Response.fromStream(streamed);
    return _parse(response);
  }

  ApiResponse<Map<String, dynamic>> _parse(http.Response response) {
    Map<String, dynamic>? json;
    try {
      final decoded = jsonDecode(response.body);
      if (decoded is Map<String, dynamic>) json = decoded;
    } catch (_) {}

    final ok = response.statusCode >= 200 && response.statusCode < 300;
    final message = json?['message']?.toString() ??
        json?['errors']?.toString() ??
        response.reasonPhrase;

    if (!ok) {
      throw ApiException(message ?? 'Request failed', statusCode: response.statusCode);
    }

    return ApiResponse(
      success: true,
      data: json,
      message: message,
      statusCode: response.statusCode,
    );
  }
}
