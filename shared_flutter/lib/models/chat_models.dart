class ChatMessage {
  ChatMessage({
    required this.id,
    required this.userId,
    required this.message,
    required this.createdAt,
    required this.isMine,
    this.senderName,
  });

  factory ChatMessage.fromJson(Map<String, dynamic> json, {required String currentUserId}) {
    final user = json['user'];
    String? name;
    if (user is Map) {
      name = '${user['first_name'] ?? ''} ${user['last_name'] ?? ''}'.trim();
    }
    return ChatMessage(
      id: '${json['id'] ?? ''}',
      userId: '${json['user_id'] ?? ''}',
      message: '${json['message'] ?? ''}',
      createdAt: '${json['created_at'] ?? ''}',
      isMine: '${json['user_id'] ?? ''}' == currentUserId,
      senderName: name,
    );
  }

  final String id;
  final String userId;
  final String message;
  final String createdAt;
  final bool isMine;
  final String? senderName;
}

class ChatChannel {
  const ChatChannel({required this.id, required this.tripId});

  factory ChatChannel.fromJson(Map<String, dynamic> json) {
    return ChatChannel(
      id: '${json['id'] ?? ''}',
      tripId: '${json['trip_id'] ?? json['channelable_id'] ?? ''}',
    );
  }

  final String id;
  final String tripId;
}
