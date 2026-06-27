import 'package:flutter/material.dart';

import '../theme/fleti_theme.dart';

class ResponsiveShell extends StatelessWidget {
  const ResponsiveShell({
    super.key,
    required this.title,
    required this.body,
    this.actions,
    this.navItems = const [],
    this.selectedNavIndex = 0,
    this.onNavSelected,
  });

  final String title;
  final Widget body;
  final List<Widget>? actions;
  final List<NavigationDestination> navItems;
  final int selectedNavIndex;
  final ValueChanged<int>? onNavSelected;

  @override
  Widget build(BuildContext context) {
    return LayoutBuilder(
      builder: (context, constraints) {
        final wide = constraints.maxWidth >= 900;
        if (wide) {
          return Scaffold(
            body: Row(
              children: [
                NavigationRail(
                  selectedIndex: selectedNavIndex,
                  onDestinationSelected: onNavSelected,
                  labelType: NavigationRailLabelType.all,
                  destinations: navItems
                      .map((d) => NavigationRailDestination(
                            icon: d.icon,
                            selectedIcon: d.selectedIcon ?? d.icon,
                            label: Text(d.label),
                          ))
                      .toList(),
                ),
                const VerticalDivider(width: 1),
                Expanded(
                  child: Column(
                    children: [
                      _TopBar(title: title, actions: actions),
                      Expanded(child: _ContentArea(child: body)),
                    ],
                  ),
                ),
              ],
            ),
          );
        }

        return Scaffold(
          appBar: AppBar(title: Text(title), actions: actions),
          body: _ContentArea(child: body),
          bottomNavigationBar: navItems.isEmpty
              ? null
              : NavigationBar(
                  selectedIndex: selectedNavIndex,
                  onDestinationSelected: onNavSelected,
                  destinations: navItems,
                ),
        );
      },
    );
  }
}

class _TopBar extends StatelessWidget {
  const _TopBar({required this.title, this.actions});

  final String title;
  final List<Widget>? actions;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 16),
      decoration: const BoxDecoration(
        color: FletiColors.surface,
        border: Border(bottom: BorderSide(color: FletiColors.border)),
      ),
      child: Row(
        children: [
          Text(title, style: Theme.of(context).textTheme.titleLarge),
          const Spacer(),
          if (actions != null) ...actions!,
        ],
      ),
    );
  }
}

class _ContentArea extends StatelessWidget {
  const _ContentArea({required this.child});

  final Widget child;

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.topCenter,
      child: ConstrainedBox(
        constraints: const BoxConstraints(maxWidth: 1200),
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: child,
        ),
      ),
    );
  }
}
