import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';
import 'events_screen.dart';
import 'scan_screen.dart';
import 'tickets_screen.dart';

class HomeShell extends StatefulWidget {
  const HomeShell({super.key, required this.user});

  final AppUser user;

  @override
  State<HomeShell> createState() => _HomeShellState();
}

class _HomeShellState extends State<HomeShell> {
  int _index = 0;

  @override
  Widget build(BuildContext context) {
    final organizer = widget.user.canOrganize;

    final pages = <Widget>[
      const EventsScreen(),
      const TicketsScreen(),
      if (organizer) const ScanScreen(),
      _AccountScreen(user: widget.user),
    ];

    final destinations = <NavigationDestination>[
      const NavigationDestination(icon: Icon(Icons.explore_outlined), selectedIcon: Icon(Icons.explore), label: 'Events'),
      const NavigationDestination(icon: Icon(Icons.confirmation_number_outlined), selectedIcon: Icon(Icons.confirmation_number), label: 'Tickets'),
      if (organizer) const NavigationDestination(icon: Icon(Icons.qr_code_scanner_outlined), selectedIcon: Icon(Icons.qr_code_scanner), label: 'Scan'),
      const NavigationDestination(icon: Icon(Icons.person_outline), selectedIcon: Icon(Icons.person), label: 'Account'),
    ];

    return Scaffold(
      body: IndexedStack(index: _index, children: pages),
      bottomNavigationBar: NavigationBar(
        selectedIndex: _index,
        onDestinationSelected: (i) => setState(() => _index = i),
        destinations: destinations,
      ),
    );
  }
}

class _AccountScreen extends StatelessWidget {
  const _AccountScreen({required this.user});

  final AppUser user;

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 20, 20, 32),
        children: [
          const Text('Account', style: TextStyle(fontSize: 30, fontWeight: FontWeight.w800, letterSpacing: -0.6)),
          const SizedBox(height: 20),
          FadeSlideIn(
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Brand.ink800,
                borderRadius: BorderRadius.circular(22),
                border: Border.all(color: Colors.white.withValues(alpha: 0.08)),
              ),
              child: Row(
                children: [
                  Container(
                    width: 60,
                    height: 60,
                    alignment: Alignment.center,
                    decoration: const BoxDecoration(gradient: Brand.brandGradient, shape: BoxShape.circle),
                    child: Text(user.initials, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(user.name, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700), overflow: TextOverflow.ellipsis),
                        const SizedBox(height: 2),
                        Text(user.email, style: const TextStyle(color: Brand.ink300, fontSize: 13.5), overflow: TextOverflow.ellipsis),
                        const SizedBox(height: 10),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(color: Brand.violet.withValues(alpha: 0.18), borderRadius: BorderRadius.circular(99)),
                          child: Text(user.role.toUpperCase(), style: const TextStyle(color: Brand.violetLight, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 1)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          _tile(Icons.public, 'Open the website', 'Buy tickets, manage events', () => openUrl(context, Api.baseUrl)),
          _tile(Icons.monitor_heart_outlined, 'System status', 'Live health of the server', () => openUrl(context, '${Api.baseUrl}/health')),
          const SizedBox(height: 20),
          OutlinedButton.icon(
            onPressed: () => Api.instance.logout(),
            style: OutlinedButton.styleFrom(
              foregroundColor: Brand.flame,
              side: BorderSide(color: Brand.flame.withValues(alpha: 0.4)),
              padding: const EdgeInsets.symmetric(vertical: 16),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
            ),
            icon: const Icon(Icons.logout),
            label: const Text('Sign out', style: TextStyle(fontWeight: FontWeight.w700)),
          ),
          const SizedBox(height: 24),
          const Center(child: Text('EventPass for Android · built with Flutter', style: TextStyle(color: Brand.ink300, fontSize: 12))),
        ],
      ),
    );
  }

  Widget _tile(IconData icon, String title, String subtitle, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10),
      child: Material(
        color: Brand.ink800,
        borderRadius: BorderRadius.circular(16),
        child: InkWell(
          borderRadius: BorderRadius.circular(16),
          onTap: onTap,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                Icon(icon, color: Brand.violetLight),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(title, style: const TextStyle(fontWeight: FontWeight.w700)),
                      Text(subtitle, style: const TextStyle(color: Brand.ink300, fontSize: 12.5)),
                    ],
                  ),
                ),
                const Icon(Icons.chevron_right, color: Brand.ink300),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
