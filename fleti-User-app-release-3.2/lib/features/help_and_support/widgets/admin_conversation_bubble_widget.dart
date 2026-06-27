import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/common_widgets/image_widget.dart';
import 'package:ride_sharing_user_app/features/message/domain/models/message_model.dart';
import 'package:ride_sharing_user_app/features/profile/controllers/profile_controller.dart';
import 'package:ride_sharing_user_app/features/splash/controllers/config_controller.dart';
import 'package:ride_sharing_user_app/helper/date_converter.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/images.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class AdminConversationBubbleWidget extends StatelessWidget {
  final Message message;
  final Message? previousMessage;
  final int index;
  final int length;

  const AdminConversationBubbleWidget({
    super.key,
    required this.message,
    this.previousMessage,
    required this.index,
    required this.length,
  });

  @override
  Widget build(BuildContext context) {
    final customerId = Get.find<ProfileController>().profileModel?.data?.id;
    final isMe = message.user?.id == customerId;
    final adminImageBase = Get.find<ConfigController>().config?.imageBaseUrl?.profileImageAdmin;
    final conversationBase = Get.find<ConfigController>().config?.imageBaseUrl?.conversation;

    return Padding(
      padding: EdgeInsets.fromLTRB(
        isMe ? 40 : Dimensions.paddingSizeDefault,
        4,
        isMe ? Dimensions.paddingSizeDefault : 40,
        4,
      ),
      child: Column(
        crossAxisAlignment: isMe ? CrossAxisAlignment.end : CrossAxisAlignment.start,
        children: [
          if (index == length - 1)
            Padding(
              padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeSmall),
              child: Center(
                child: Text(
                  DateConverter.stringToLocalDateOnly(message.createdAt ?? DateTime.now().toString()),
                  style: textRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall),
                ),
              ),
            ),
          Row(
            crossAxisAlignment: CrossAxisAlignment.end,
            mainAxisAlignment: isMe ? MainAxisAlignment.end : MainAxisAlignment.start,
            children: [
              if (!isMe)
                ClipRRect(
                  borderRadius: BorderRadius.circular(50),
                  child: ImageWidget(
                    height: 28,
                    width: 28,
                    image: '${adminImageBase ?? ''}/${message.user?.profileImage ?? ''}',
                    placeholder: Images.personPlaceholder,
                  ),
                ),
              if (!isMe) const SizedBox(width: Dimensions.paddingSizeSmall),
              Flexible(
                child: Container(
                  padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                  decoration: BoxDecoration(
                    color: isMe
                        ? Theme.of(context).primaryColor.withValues(alpha: 0.9)
                        : Theme.of(context).primaryColor.withValues(alpha: 0.08),
                    borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      if (message.message != null && message.message!.isNotEmpty)
                        Text(
                          message.message!,
                          style: textRegular.copyWith(
                            color: isMe ? Theme.of(context).cardColor : null,
                          ),
                        ),
                      if (message.conversationFiles != null && message.conversationFiles!.isNotEmpty)
                        ...message.conversationFiles!.map((file) {
                          final url = '$conversationBase/${file.fileName ?? ''}';
                          if (file.fileType == 'png' || file.fileType == 'jpg' || file.fileType == 'jpeg') {
                            return Padding(
                              padding: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: ImageWidget(height: 120, width: 120, image: url, fit: BoxFit.cover),
                              ),
                            );
                          }
                          return Padding(
                            padding: const EdgeInsets.only(top: Dimensions.paddingSizeSmall),
                            child: Text(file.fileName ?? '', style: textRegular.copyWith(fontSize: Dimensions.fontSizeSmall)),
                          );
                        }),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
