import 'package:file_picker/file_picker.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';
import 'package:ride_sharing_user_app/features/help_and_support/domain/repositories/help_and_support_repository_interface.dart';
import 'package:ride_sharing_user_app/features/help_and_support/domain/services/help_and_support_service_interface.dart';

class HelpAndSupportService implements HelpAndSupportServiceInterface {
  final HelpAndSupportRepositoryInterface helpAndSupportRepositoryInterface;
  const HelpAndSupportService({required this.helpAndSupportRepositoryInterface});

  @override
  Future createChannel() => helpAndSupportRepositoryInterface.createChannel();

  @override
  Future sendMessage({
    String? message,
    String? channelId,
    List<MultipartBody>? images,
    PlatformFile? platformFile,
  }) {
    return helpAndSupportRepositoryInterface.sendMessage(
      message: message,
      channelId: channelId,
      images: images,
      platformFile: platformFile,
    );
  }

  @override
  Future sendFaqMessage({String? questionId, String? channelId}) {
    return helpAndSupportRepositoryInterface.sendFaqMessage(questionId: questionId, channelId: channelId);
  }

  @override
  Future getConversation(String channelId, int offset) {
    return helpAndSupportRepositoryInterface.getConversation(channelId, offset);
  }

  @override
  Future getPredefineFaqList() => helpAndSupportRepositoryInterface.getPredefineFaqList();

  @override
  Future submitServiceRequest({required String channelId, required Map<String, dynamic> body}) {
    return helpAndSupportRepositoryInterface.submitServiceRequest(channelId: channelId, body: body);
  }
}
