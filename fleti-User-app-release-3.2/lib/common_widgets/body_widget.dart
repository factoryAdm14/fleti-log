import 'package:flutter/material.dart';
import 'package:ride_sharing_user_app/common_widgets/app_bar_widget.dart';
import 'package:ride_sharing_user_app/theme/fleti_modern_decorations.dart';
import 'package:ride_sharing_user_app/util/dimensions.dart';

class BodyWidget extends StatefulWidget {
  final Widget body;
  final AppBarWidget appBar;
  final double topMargin;
  const BodyWidget({super.key, required this.body, required this.appBar, this.topMargin = 10});

  @override
  State<BodyWidget> createState() => _BodyWidgetState();
}

class _BodyWidgetState extends State<BodyWidget> {
  @override
  Widget build(BuildContext context) {
    return  Column(children: [
      widget.appBar,

      Expanded(child: Container(
        margin: EdgeInsets.only(top: widget.topMargin),
        width: Dimensions.webMaxWidth,
        decoration: FletiModernDecorations.bodyPanel(context),
        child: ClipRRect(
          borderRadius: const BorderRadius.only(
            topRight: Radius.circular(Dimensions.radiusExtraLarge),
            topLeft: Radius.circular(Dimensions.radiusExtraLarge),
          ),
            child: widget.body,
        ),
      )),

    ]);
  }
}
