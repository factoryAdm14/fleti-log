import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter_spinkit/flutter_spinkit.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/body_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/custom_pop_scope_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/no_data_widget.dart';
import 'package:ride_sharing_user_app/common_widgets/paginated_list_widget.dart';
import 'package:ride_sharing_user_app/features/help_and_support/controllers/help_and_support_controller.dart';
import 'package:ride_sharing_user_app/features/help_and_support/widgets/admin_conversation_bubble_widget.dart';
import 'package:ride_sharing_user_app/features/help_and_support/widgets/service_request_bottom_sheet.dart';
import 'package:ride_sharing_user_app/features/notification/widgets/notification_shimmer.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/localization/localization_controller.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class SupportChatScreen extends StatefulWidget {
  final String channelId;
  const SupportChatScreen({super.key, required this.channelId});

  @override
  State<SupportChatScreen> createState() => _SupportChatScreenState();
}

class _SupportChatScreenState extends State<SupportChatScreen> {
  final ScrollController scrollController = ScrollController();

  @override
  void initState() {
    super.initState();
    if (Get.find<ConfigController>().config?.customerQuestionAnswerStatus ?? false) {
      Get.find<HelpAndSupportController>().getPredefineFaqList();
    }
    Get.find<HelpAndSupportController>().getConversation(widget.channelId, 1);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      top: false,
      child: CustomPopScopeWidget(
        child: Scaffold(
          floatingActionButton: FloatingActionButton.extended(
            onPressed: () {
              showModalBottomSheet(
                context: context,
                isScrollControlled: true,
                backgroundColor: Theme.of(context).cardColor,
                shape: const RoundedRectangleBorder(
                  borderRadius: BorderRadius.vertical(top: Radius.circular(Dimensions.paddingSizeDefault)),
                ),
                builder: (_) => ServiceRequestBottomSheet(channelId: widget.channelId),
              );
            },
            icon: const Icon(Icons.add_road),
            label: Text('service_request'.tr),
          ),
          body: BodyWidget(
            appBar: AppBarWidget(title: 'admin'.tr, showBackButton: true, centerTitle: true),
            body: GetBuilder<HelpAndSupportController>(builder: (controller) {
              return Stack(children: [
                Column(children: [
                  controller.messageModel?.data != null
                      ? controller.messageModel!.data!.isNotEmpty
                          ? Expanded(
                              child: SingleChildScrollView(
                                controller: scrollController,
                                reverse: true,
                                child: PaginatedListWidget(
                                  reverse: true,
                                  scrollController: scrollController,
                                  totalSize: controller.messageModel?.totalSize,
                                  offset: controller.messageModel?.offset != null
                                      ? int.parse(controller.messageModel!.offset.toString())
                                      : null,
                                  onPaginate: (offset) async {
                                    await controller.getConversation(widget.channelId, offset!);
                                  },
                                  itemView: ListView.builder(
                                    reverse: true,
                                    itemCount: controller.messageModel?.data?.length ?? 0,
                                    padding: EdgeInsets.zero,
                                    physics: const NeverScrollableScrollPhysics(),
                                    shrinkWrap: true,
                                    itemBuilder: (context, index) {
                                      return AdminConversationBubbleWidget(
                                        message: controller.messageModel!.data![index],
                                        previousMessage: index > 0 ? controller.messageModel!.data![index - 1] : null,
                                        index: index,
                                        length: controller.messageModel!.data!.length,
                                      );
                                    },
                                  ),
                                ),
                              ),
                            )
                          : const Expanded(child: NoDataWidget(title: 'start_conversation'))
                      : const Expanded(child: NotificationShimmer()),
                  _buildInputBar(controller),
                ]),
                if (controller.showFaqQuestions) _buildFaqPanel(controller),
              ]);
            }),
          ),
        ),
      ),
    );
  }

  Widget _buildInputBar(HelpAndSupportController controller) {
    return Column(children: [
      if (controller.pickedImageFile != null && controller.pickedImageFile!.isNotEmpty)
        SizedBox(
          height: 90,
          child: ListView.builder(
            scrollDirection: Axis.horizontal,
            itemCount: controller.pickedImageFile!.length,
            itemBuilder: (context, index) {
              return Stack(children: [
                Padding(
                  padding: const EdgeInsets.all(8),
                  child: ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.file(File(controller.pickedImageFile![index].path), height: 70, width: 70, fit: BoxFit.cover),
                  ),
                ),
                Positioned(
                  right: 0,
                  top: 0,
                  child: InkWell(
                    onTap: () => controller.pickMultipleImage(true, index: index),
                    child: const Icon(Icons.cancel, color: Colors.grey),
                  ),
                ),
              ]);
            },
          ),
        ),
      if (controller.otherFile != null)
        Padding(
          padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
          child: Row(children: [
            Expanded(child: Text(controller.otherFile!.files.single.name, maxLines: 1, overflow: TextOverflow.ellipsis)),
            InkWell(onTap: controller.removeFile, child: Image.asset(Images.crossIcon, height: 16, width: 16)),
          ]),
        ),
      Row(children: [
        Expanded(
          child: Container(
            margin: const EdgeInsets.all(Dimensions.paddingSizeSmall),
            decoration: BoxDecoration(
              border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.25)),
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
            ),
            child: TextField(
              controller: controller.conversationController,
              maxLines: 2,
              decoration: InputDecoration(
                border: InputBorder.none,
                contentPadding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                hintText: 'type_here'.tr,
              ),
              onChanged: (_) => controller.updateShowFaq(false, isUpdate: true),
            ),
          ),
        ),
        Padding(
          padding: const EdgeInsets.only(right: Dimensions.paddingSizeSmall),
          child: InkWell(
            onTap: () => controller.pickMultipleImage(false),
            child: Image.asset(Images.pickImage, height: 22, width: 22, color: Theme.of(context).primaryColor),
          ),
        ),
        Padding(
          padding: EdgeInsets.only(
            right: Get.find<LocalizationController>().isLtr ? Dimensions.paddingSizeDefault : 0,
            left: Get.find<LocalizationController>().isLtr ? 0 : Dimensions.paddingSizeDefault,
          ),
          child: controller.isSending || controller.isImagePicked
              ? SpinKitCircle(color: Theme.of(context).primaryColor, size: 22)
              : InkWell(
                  onTap: () {
                    final showFaq = Get.find<ConfigController>().config?.customerQuestionAnswerStatus ?? false;
                    if (showFaq &&
                        controller.conversationController.text.trim().isEmpty &&
                        controller.otherFile == null &&
                        controller.selectedImageList.isEmpty) {
                      controller.updateShowFaq(!controller.showFaqQuestions, isUpdate: true);
                      return;
                    }
                    if (controller.conversationController.text.trim().isEmpty &&
                        (controller.pickedImageFile == null || controller.pickedImageFile!.isEmpty) &&
                        controller.otherFile == null) {
                      showCustomSnackBar('write_something'.tr, isError: true);
                      return;
                    }
                    controller.sendMessage(widget.channelId, controller.conversationController.text);
                    controller.conversationController.clear();
                  },
                  child: Image.asset(
                    ((Get.find<ConfigController>().config?.customerQuestionAnswerStatus ?? false) &&
                            controller.conversationController.text.trim().isEmpty &&
                            controller.otherFile == null &&
                            controller.selectedImageList.isEmpty)
                        ? Images.faqImageIcon
                        : Images.sendMessage,
                    width: 24,
                    height: 24,
                    color: Theme.of(context).primaryColor,
                  ),
                ),
        ),
      ]),
    ]);
  }

  Widget _buildFaqPanel(HelpAndSupportController controller) {
    return Positioned.fill(
      child: Padding(
        padding: EdgeInsets.only(bottom: Get.height * 0.12),
        child: Align(
          alignment: Alignment.bottomCenter,
          child: Container(
            margin: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            decoration: BoxDecoration(
              color: Theme.of(context).cardColor,
              borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
              boxShadow: [BoxShadow(color: Theme.of(context).hintColor.withValues(alpha: 0.1), blurRadius: 4)],
            ),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('faqs'.tr, style: textMedium),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                Flexible(
                  child: ListView.separated(
                    shrinkWrap: true,
                    itemCount: controller.predefineFawModel?.data?.length ?? 0,
                    separatorBuilder: (_, __) => const SizedBox(height: Dimensions.paddingSizeSmall),
                    itemBuilder: (context, index) {
                      final item = controller.predefineFawModel!.data![index];
                      return InkWell(
                        onTap: () {
                          controller.sendMessage(widget.channelId, item.id ?? '', fromFaq: true);
                          controller.updateShowFaq(false, isUpdate: true);
                        },
                        child: Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                          decoration: BoxDecoration(
                            border: Border.all(color: Colors.grey.withValues(alpha: 0.2)),
                            borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                          ),
                          child: Text(item.question ?? ''),
                        ),
                      );
                    },
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
