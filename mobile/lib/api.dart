import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import 'models.dart';

class ApiException implements Exception {
  ApiException(this.message, {this.status});

  final String message;
  final int? status;

  @override
  String toString() => message;
}

/// Thin client for the EventPass Laravel API (Sanctum bearer tokens).
class Api {
  Api._();
  static final Api instance = Api._();

  /// Override with `--dart-define=API_BASE=http://10.0.2.2:8000` to use a local server.
  static const baseUrl = String.fromEnvironment('API_BASE', defaultValue: 'https://eventpass-36j5.onrender.com');

  static const _tokenKey = 'token';
  static const _userKey = 'user';

  String? _token;

  /// The signed-in user, or null. Widgets listen to this to switch between login and the app.
  final ValueNotifier<AppUser?> session = ValueNotifier<AppUser?>(null);

  Future<void> load() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
    final raw = prefs.getString(_userKey);
    if (_token != null && raw != null) {
      try {
        session.value = AppUser.fromJson(jsonDecode(raw) as Map<String, dynamic>);
      } catch (_) {
        await _clear();
      }
    }
  }

  Future<void> _store(String token, AppUser user) async {
    _token = token;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_userKey, jsonEncode(user.toJson()));
    session.value = user;
  }

  Future<void> _clear() async {
    _token = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_userKey);
    session.value = null;
  }

  Future<dynamic> _request(String method, String path, {Map<String, dynamic>? body, Map<String, String>? query}) async {
    final uri = Uri.parse('$baseUrl/api$path').replace(queryParameters: query == null || query.isEmpty ? null : query);
    final headers = <String, String>{
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      if (_token != null) 'Authorization': 'Bearer $_token',
    };

    http.Response res;
    try {
      // The free server sleeps when idle, so the first call can take up to a minute.
      final call = method == 'GET' ? http.get(uri, headers: headers) : http.post(uri, headers: headers, body: jsonEncode(body ?? {}));
      res = await call.timeout(const Duration(seconds: 75));
    } on TimeoutException {
      throw ApiException('The server is waking up. Please try again in a moment.');
    } on SocketException {
      throw ApiException('No connection. Check your internet and try again.');
    } on HandshakeException {
      throw ApiException('Could not make a secure connection. Check the date and time on your phone.');
    } on http.ClientException {
      throw ApiException('Could not reach the server. Check your internet and try again.');
    } on IOException {
      throw ApiException('Network error. Check your internet and try again.');
    }

    dynamic data;
    try {
      data = res.body.isEmpty ? null : jsonDecode(res.body);
    } catch (_) {
      throw ApiException('Unexpected response from the server (${res.statusCode}).', status: res.statusCode);
    }

    if (res.statusCode == 401 && _token != null) {
      await _clear();
      throw ApiException('Your session expired. Please sign in again.', status: 401);
    }
    if (res.statusCode >= 400) {
      final message = data is Map && data['message'] is String ? data['message'] as String : 'Something went wrong (${res.statusCode}).';
      throw ApiException(message, status: res.statusCode);
    }
    return data;
  }

  // ---- auth ----

  Future<void> login(String email, String password) async {
    final data = await _request('POST', '/login', body: {'email': email, 'password': password, 'device_name': 'android'});
    await _store(data['token'] as String, AppUser.fromJson(data['user'] as Map<String, dynamic>));
  }

  Future<void> demo(String role) async {
    final data = await _request('POST', '/login/demo/$role');
    await _store(data['token'] as String, AppUser.fromJson(data['user'] as Map<String, dynamic>));
  }

  Future<void> logout() async {
    try {
      await _request('POST', '/logout');
    } catch (_) {
      // Signing out locally is enough if the server is unreachable.
    }
    await _clear();
  }

  // ---- data ----

  Future<List<EventItem>> events({String? search, String? category}) async {
    final data = await _request('GET', '/events', query: {
      if (search != null && search.trim().isNotEmpty) 'q': search.trim(),
      'category': ?category,
    });
    return (data['data'] as List).map((e) => EventItem.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<EventItem> event(String slug) async {
    final data = await _request('GET', '/events/$slug');
    return EventItem.fromJson(data['data'] as Map<String, dynamic>);
  }

  Future<List<Ticket>> tickets() async {
    final data = await _request('GET', '/tickets');
    return (data['data'] as List).map((e) => Ticket.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<List<OrganizerEvent>> organizerEvents() async {
    final data = await _request('GET', '/organizer/events');
    return (data['data'] as List).map((e) => OrganizerEvent.fromJson(e as Map<String, dynamic>)).toList();
  }

  Future<ScanResult> checkIn(String slug, String code) async {
    final data = await _request('POST', '/organizer/events/$slug/checkin', body: {'code': code});
    return ScanResult.fromJson(data as Map<String, dynamic>);
  }
}
