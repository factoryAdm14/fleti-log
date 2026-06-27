import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/features/help_and_support/controllers/help_and_support_controller.dart';
import 'package:ride_sharing_user_app/helper/display_helper.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';
import 'package:ride_sharing_user_app/util/styles.dart';

class ServiceRequestBottomSheet extends StatefulWidget {
  final String channelId;
  const ServiceRequestBottomSheet({super.key, required this.channelId});

  @override
  State<ServiceRequestBottomSheet> createState() => _ServiceRequestBottomSheetState();
}

class _ServiceRequestBottomSheetState extends State<ServiceRequestBottomSheet> {
  final TextEditingController _originController = TextEditingController();
  final TextEditingController _destinationController = TextEditingController();
  final TextEditingController _notesController = TextEditingController();
  String _serviceType = 'ride';

  @override
  void dispose() {
    _originController.dispose();
    _destinationController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: Dimensions.paddingSizeLarge,
        right: Dimensions.paddingSizeLarge,
        top: Dimensions.paddingSizeLarge,
        bottom: MediaQuery.of(context).viewInsets.bottom + Dimensions.paddingSizeLarge,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('service_request'.tr, style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge)),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          Row(
            children: [
              Expanded(
                child: ChoiceChip(
                  label: Text('ride'.tr),
                  selected: _serviceType == 'ride',
                  onSelected: (_) => setState(() => _serviceType = 'ride'),
                ),
              ),
              const SizedBox(width: Dimensions.paddingSizeSmall),
              Expanded(
                child: ChoiceChip(
                  label: Text('delivery'.tr),
                  selected: _serviceType == 'delivery',
                  onSelected: (_) => setState(() => _serviceType = 'delivery'),
                ),
              ),
            ],
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          TextField(
            controller: _originController,
            decoration: InputDecoration(
              labelText: 'origin'.tr,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          TextField(
            controller: _destinationController,
            decoration: InputDecoration(
              labelText: 'destination'.tr,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeDefault),
          TextField(
            controller: _notesController,
            maxLines: 2,
            decoration: InputDecoration(
              labelText: 'notes'.tr,
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall)),
            ),
          ),
          const SizedBox(height: Dimensions.paddingSizeLarge),
          GetBuilder<HelpAndSupportController>(builder: (controller) {
            return SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: controller.isSending ? null : () async {
                  if (_originController.text.trim().isEmpty || _destinationController.text.trim().isEmpty) {
                    showCustomSnackBar('fill_all_required_fill'.tr, isError: true);
                    return;
                  }
                  await controller.submitServiceRequest(
                    channelId: widget.channelId,
                    serviceType: _serviceType,
                    originAddress: _originController.text.trim(),
                    destinationAddress: _destinationController.text.trim(),
                    notes: _notesController.text.trim(),
                  );
                  if (mounted) Get.back();
                },
                child: controller.isSending
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                    : Text('submit'.tr),
              ),
            );
          }),
        ],
      ),
    );
  }
}
