import 'package:file_picker/file_picker.dart';
import 'package:get/get_connect/http/src/response/response.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';
import 'package:ride_sharing_user_app/interface/repository_interface.dart';

abstract class HelpAndSupportRepositoryInterface implements RepositoryInterface {
  Future<Response> createChannel();
  Future<Response> getPredefineFaqList();
  Future<Response> sendMessage({
    String? message,
    String? channelId,
    List<MultipartBody>? images,
    PlatformFile? platformFile,
  });
  Future<Response> sendFaqMessage({String? questionId, String? channelId});
  Future<Response> getConversation(String channelId, int offset);
  Future<Response> submitServiceRequest({required String channelId, required Map<String, dynamic> body});
}
