import '../core/api_constants.dart';
import '../core/app_role.dart';
import '../models/chat_models.dart';
import 'api_service.dart';

class ChatService {
  ChatService(this._api, this._role);

  final ApiService _api;
  final AppRole _role;

  String get _prefix => _role == AppRole.customer ? 'customer' : 'driver';

  Future<ChatChannel> createOrGetChannel({
    required String toUserId,
    required String tripId,
  }) async {
    final response = await _api.post(
      '${ApiConstants.baseUrl}/api/$_prefix/chat/create-channel',
      body: {
        'to': toUserId,
        'trip_id': tripId,
        '_method': 'put',
      },
    );
    final data = response.data?['data'];
    if (data is Map<String, dynamic>) {
      final channel = data['channel'];
      if (channel is Map<String, dynamic>) {
        return ChatChannel.fromJson(channel);
      }
    }
    throw Exception('Não foi possível abrir o chat.');
  }

  Future<List<ChatMessage>> getConversation({
    required String channelId,
    required String currentUserId,
    int limit = 30,
    int offset = 1,
  }) async {
    final response = await _api.get(
      '${ApiConstants.baseUrl}/api/$_prefix/chat/conversation?channel_id=$channelId&limit=$limit&offset=$offset',
    );
    final data = response.data?['data'];
    if (data is! List) return [];
    return data
        .whereType<Map<String, dynamic>>()
        .map((m) => ChatMessage.fromJson(m, currentUserId: currentUserId))
        .toList();
  }

  Future<void> sendMessage({
    required String channelId,
    required String tripId,
    required String message,
  }) async {
    await _api.post(
      '${ApiConstants.baseUrl}/api/$_prefix/chat/send-message',
      body: {
        'channel_id': channelId,
        'trip_id': tripId,
        'message': message,
        '_method': 'put',
      },
    );
  }
}
