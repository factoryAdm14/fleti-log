import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:ride_sharing_user_app/util/app_constants.dart';

const sfProLight = TextStyle(
  fontFamily: AppConstants.fontFamily,
  fontWeight: FontWeight.w300,
);

const textRegular = TextStyle(
  fontFamily: AppConstants.fontFamily,
  fontWeight: FontWeight.w400,
);

const textMedium = TextStyle(
  fontFamily: AppConstants.fontFamily,
  fontWeight: FontWeight.w500,
);

const textSemiBold = TextStyle(
  fontFamily: AppConstants.fontFamily,
  fontWeight: FontWeight.w600,
);

const textBold = TextStyle(
  fontFamily: AppConstants.fontFamily,
  fontWeight: FontWeight.w700,
);

const textHeavy = TextStyle(
  fontFamily: AppConstants.fontFamily,
  fontWeight: FontWeight.w900,
);

const textRobotoRegular = TextStyle(
  fontFamily: 'Roboto',
  fontWeight: FontWeight.w400,
);

const textRobotoMedium = TextStyle(
  fontFamily: 'Roboto',
  fontWeight: FontWeight.w500,
);

const textRobotoBold = TextStyle(
  fontFamily: 'Roboto',
  fontWeight: FontWeight.w700,
);

const textRobotoBlack = TextStyle(
  fontFamily: 'Roboto',
  fontWeight: FontWeight.w900,
);

List<BoxShadow>? searchBoxShadow = Get.isDarkMode ? null : [
  BoxShadow(
    offset: const Offset(0, 2),
    color: Colors.black.withValues(alpha: 0.06),
    blurRadius: 8,
    spreadRadius: 0,
  ),
];

List<BoxShadow>? cardShadow = Get.isDarkMode
    ? null
    : [
        BoxShadow(
          offset: const Offset(0, 1),
          color: Colors.black.withValues(alpha: 0.05),
          blurRadius: 6,
          spreadRadius: 0,
        ),
      ];

List<BoxShadow>? shadow = Get.isDarkMode
    ? [
        BoxShadow(
          offset: const Offset(0, 2),
          color: Colors.black.withValues(alpha: 0.2),
          blurRadius: 6,
          spreadRadius: 0,
        ),
      ]
    : [
        BoxShadow(
          offset: const Offset(0, 2),
          color: Colors.black.withValues(alpha: 0.04),
          blurRadius: 6,
          spreadRadius: 0,
        ),
      ];


