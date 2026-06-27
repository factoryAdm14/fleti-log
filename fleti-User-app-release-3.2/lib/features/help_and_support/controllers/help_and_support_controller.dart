import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:image_picker/image_picker.dart';
import 'package:ride_sharing_user_app/data/api_checker.dart';
import 'package:ride_sharing_user_app/data/api_client.dart';
import 'package:ride_sharing_user_app/features/help_and_support/domain/models/predefined_faq_model.dart';
import 'package:ride_sharing_user_app/features/help_and_support/domain/services/help_and_support_service_interface.dart';
import 'package:ride_sharing_user_app/features/help_and_support/screens/support_chat_screen.dart';
import 'package:ride_sharing_user_app/features/message/domain/models/message_model.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/helper/file_validation_helper.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

class HelpAndSupportController extends GetxController implements GetxService {
  final HelpAndSupportServiceInterface helpAndSupportServiceInterface;
  HelpAndSupportController({required this.helpAndSupportServiceInterface});

  MessageModel? messageModel;
  List<XFile>? _pickedImageFiles = [];
  List<XFile>? get pickedImageFile => _pickedImageFiles;
  bool isImagePicked = false;
  PlatformFile? objFile;
  FilePickerResult? _otherFile;
  FilePickerResult? get otherFile => _otherFile;
  List<MultipartBody> _selectedImageList = [];
  List<MultipartBody> get selectedImageList => _selectedImageList;
  var conversationController = TextEditingController();
  final GlobalKey<FormState> textKey = GlobalKey<FormState>();
  bool isLoading = false;
  bool isSending = false;
  bool showFaqQuestions = false;
  PredefineFawModel? predefineFawModel;

  void updateShowFaq(bool action, {bool isUpdate = false}) {
    showFaqQuestions = action;
    if (isUpdate) update();
  }

  void pickMultipleImage(bool isRemove, {int? index}) async {
    showFaqQuestions = false;
    if (isRemove) {
      if (index != null) {
        _pickedImageFiles!.removeAt(index);
        _selectedImageList.removeAt(index);
      }
    } else {
      isImagePicked = true;
      update();
      _pickedImageFiles = await FileValidationHelper.validateAndPickMultipleImages();
      _selectedImageList = [];
      if (_pickedImageFiles != null) {
        for (int i = 0; i < _pickedImageFiles!.length; i++) {
          _selectedImageList.add(MultipartBody('files[$i]', _pickedImageFiles![i]));
        }
      }
      isImagePicked = false;
    }
    update();
  }

  Future<void> pickOtherFile() async {
    showFaqQuestions = false;
    _otherFile = await FilePicker.pickFiles(
      type: FileType.custom,
      withReadStream: true,
      allowedExtensions: AppConstants.allowedImageExtensionsForFile,
    );
    if (_otherFile != null) {
      if (await FileValidationHelper.validatePlatformFileSizeAsync(file: _otherFile!.files.single)) {
        objFile = _otherFile!.files.single;
      }
    }
    update();
  }

  void removeFile() {
    _otherFile = null;
    objFile = null;
    update();
  }

  Future<void> createChannel({bool fromSplash = false}) async {
    isLoading = true;
    update();
    Response response = await helpAndSupportServiceInterface.createChannel();
    if (response.statusCode == 200) {
      isLoading = false;
      String channelId = response.body['data']['channel']['id'];
      if (fromSplash) {
        Get.offAll(() => SupportChatScreen(channelId: channelId));
      } else {
        Get.to(() => SupportChatScreen(channelId: channelId));
      }
    } else {
      isLoading = false;
      ApiChecker.checkApi(response);
    }
    update();
  }

  Future<void> sendMessage(String channelId, String message, {bool fromFaq = false}) async {
    isSending = true;
    update();
    Response response = fromFaq
        ? await helpAndSupportServiceInterface.sendFaqMessage(questionId: message, channelId: channelId)
        : await helpAndSupportServiceInterface.sendMessage(
            message: message,
            channelId: channelId,
            images: _selectedImageList,
            platformFile: objFile,
          );
    if (response.statusCode == 200) {
      isSending = false;
      getConversation(channelId, 1);
      conversationController.text = '';
      _pickedImageFiles = [];
      _selectedImageList = [];
      _otherFile = null;
      objFile = null;
    } else if (response.statusCode == 400) {
      isSending = false;
      String errorMessage = response.body['errors'][0]['message'];
      showCustomSnackBar(errorMessage.tr);
      _pickedImageFiles = [];
      _selectedImageList = [];
      _otherFile = null;
      objFile = null;
    } else {
      isSending = false;
      _pickedImageFiles = [];
      _selectedImageList = [];
      _otherFile = null;
      objFile = null;
      ApiChecker.checkApi(response);
    }
    update();
  }

  Future<Response> getConversation(String channelId, int offset) async {
    isLoading = true;
    Response response = await helpAndSupportServiceInterface.getConversation(channelId, offset);
    if (response.statusCode == 200) {
      if (offset == 1) {
        messageModel = MessageModel.fromJson(response.body);
      } else {
        messageModel!.totalSize = MessageModel.fromJson(response.body).totalSize;
        messageModel!.offset = MessageModel.fromJson(response.body).offset;
        messageModel!.data!.addAll(MessageModel.fromJson(response.body).data!);
      }
      isLoading = false;
    } else {
      isLoading = false;
      ApiChecker.checkApi(response);
    }
    update();
    return response;
  }

  void getPredefineFaqList() async {
    Response response = await helpAndSupportServiceInterface.getPredefineFaqList();
    if (response.statusCode == 200) {
      predefineFawModel = PredefineFawModel.fromJson(response.body);
      update();
    } else {
      ApiChecker.checkApi(response);
    }
  }

  Future<void> submitServiceRequest({
    required String channelId,
    required String serviceType,
    required String originAddress,
    required String destinationAddress,
    String? notes,
  }) async {
    isSending = true;
    update();
    Response response = await helpAndSupportServiceInterface.submitServiceRequest(
      channelId: channelId,
      body: {
        'service_type': serviceType,
        'origin_address': originAddress,
        'destination_address': destinationAddress,
        if (notes != null && notes.isNotEmpty) 'notes': notes,
      },
    );
    isSending = false;
    if (response.statusCode == 200) {
      getConversation(channelId, 1);
    } else {
      ApiChecker.checkApi(response);
    }
    update();
  }
}
