import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:ride_sharing_user_app/common_widgets/modern/modern_card.dart';

void main() {
  testWidgets('ModernCard renders child without overflow', (tester) async {
    await tester.pumpWidget(
      MaterialApp(
        home: Scaffold(
          body: SizedBox(
            width: 320,
            child: ModernCard(
              child: Column(
                children: List.generate(
                  3,
                  (index) => Padding(
                    padding: const EdgeInsets.all(8),
                    child: Text('Row $index'),
                  ),
                ),
              ),
            ),
          ),
        ),
      ),
    );

    expect(find.text('Row 0'), findsOneWidget);
    expect(tester.takeException(), isNull);
  });
}
