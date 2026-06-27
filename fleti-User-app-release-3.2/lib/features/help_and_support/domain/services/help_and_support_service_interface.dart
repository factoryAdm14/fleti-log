import 'package:file_picker/file_picker.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';

abstract class HelpAndSupportServiceInterface {
  Future<dynamic> createChannel();
  Future<dynamic> getPredefineFaqList();
  Future<dynamic> getConversation(String channelId, int offset);
  Future<dynamic> sendMessage({
    String? message,
    String? channelId,
    List<MultipartBody>? images,
    PlatformFile? platformFile,
  });
  Future<dynamic> sendFaqMessage({String? questionId, String? channelId});
  Future<dynamic> submitServiceRequest({required String channelId, required Map<String, dynamic> body});
}
