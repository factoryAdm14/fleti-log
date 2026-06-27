import 'dart:async';

import 'package:flutter/material.dart';
import 'package:shared_flutter/shared_flutter.dart';

class TripChatScreen extends StatefulWidget {
  const TripChatScreen({
    super.key,
    required this.tripId,
    required this.otherUserId,
    required this.otherUserName,
    required this.chatService,
    required this.currentUserId,
  });

  final String tripId;
  final String otherUserId;
  final String otherUserName;
  final ChatService chatService;
  final String currentUserId;

  @override
  State<TripChatScreen> createState() => _TripChatScreenState();
}

class _TripChatScreenState extends State<TripChatScreen> {
  final _controller = TextEditingController();
  final _scroll = ScrollController();
  String? _channelId;
  List<ChatMessage> _messages = [];
  bool _loading = true;
  bool _sending = false;
  String? _error;
  Timer? _pollTimer;

  @override
  void initState() {
    super.initState();
    _init();
    _pollTimer = Timer.periodic(const Duration(seconds: 8), (_) => _loadMessages(silent: true));
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    _controller.dispose();
    _scroll.dispose();
    super.dispose();
  }

  Future<void> _init() async {
    setState(() {
      _loading = true;
      _error = null;
    });
    try {
      final channel = await widget.chatService.createOrGetChannel(
        toUserId: widget.otherUserId,
        tripId: widget.tripId,
      );
      _channelId = channel.id;
      await _loadMessages();
    } catch (e) {
      if (mounted) {
        setState(() {
          _error = e.toString();
          _loading = false;
        });
      }
    }
  }

  Future<void> _loadMessages({bool silent = false}) async {
    if (_channelId == null) return;
    if (!silent) setState(() => _loading = true);
    try {
      final messages = await widget.chatService.getConversation(
        channelId: _channelId!,
        currentUserId: widget.currentUserId,
      );
      if (!mounted) return;
      setState(() {
        _messages = messages;
        _loading = false;
      });
    } catch (e) {
      if (!mounted || silent) return;
      setState(() {
        _error = e.toString();
        _loading = false;
      });
    }
  }

  Future<void> _send() async {
    final text = _controller.text.trim();
    if (text.isEmpty || _channelId == null || _sending) return;
    setState(() => _sending = true);
    try {
      await widget.chatService.sendMessage(
        channelId: _channelId!,
        tripId: widget.tripId,
        message: text,
      );
      _controller.clear();
      await _loadMessages(silent: true);
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text('$e')));
      }
    } finally {
      if (mounted) setState(() => _sending = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Chat — ${widget.otherUserName}')),
      body: _loading && _messages.isEmpty
          ? const LoadingOverlay()
          : _error != null && _channelId == null
              ? Center(child: ErrorState(message: _error!, onRetry: _init))
              : Column(
                  children: [
                    Expanded(
                      child: _messages.isEmpty
                          ? const EmptyState(title: 'Nenhuma mensagem', subtitle: 'Envie a primeira mensagem.')
                          : ListView.builder(
                              controller: _scroll,
                              padding: const EdgeInsets.all(16),
                              itemCount: _messages.length,
                              itemBuilder: (_, i) {
                                final m = _messages[i];
                                return Align(
                                  alignment: m.isMine ? Alignment.centerRight : Alignment.centerLeft,
                                  child: Container(
                                    margin: const EdgeInsets.only(bottom: 8),
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                    constraints: const BoxConstraints(maxWidth: 420),
                                    decoration: BoxDecoration(
                                      color: m.isMine
                                          ? FletiColors.primary.withValues(alpha: 0.12)
                                          : FletiColors.surface,
                                      border: Border.all(color: FletiColors.border),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Text(m.message),
                                  ),
                                );
                              },
                            ),
                    ),
                    Padding(
                      padding: const EdgeInsets.all(12),
                      child: Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: _controller,
                              decoration: const InputDecoration(
                                hintText: 'Digite sua mensagem...',
                                border: OutlineInputBorder(),
                              ),
                              onSubmitted: (_) => _send(),
                            ),
                          ),
                          const SizedBox(width: 8),
                          IconButton(
                            onPressed: _sending ? null : _send,
                            icon: _sending
                                ? const SizedBox(
                                    width: 20,
                                    height: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Icon(Icons.send),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
    );
  }
}
