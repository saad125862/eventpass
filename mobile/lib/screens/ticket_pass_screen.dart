import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../models.dart';
import '../theme.dart';

/// Full-screen pass: a large, high-contrast QR for fast scanning at the door.
class TicketPassScreen extends StatelessWidget {
  const TicketPassScreen({super.key, required this.ticket});

  final Ticket ticket;

  @override
  Widget build(BuildContext context) {
    final e = ticket.event;
    final statusColor = ticket.valid ? Brand.mint : (ticket.status == 'used' ? Brand.violetLight : Brand.flame);

    return Scaffold(
      appBar: AppBar(title: const Text('Your pass', style: TextStyle(fontWeight: FontWeight.w800))),
      body: SingleChildScrollView(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 32),
        child: Column(
          children: [
            Container(
              decoration: BoxDecoration(
                color: Brand.ink800,
                borderRadius: BorderRadius.circular(26),
                border: Border.all(color: Colors.white.withValues(alpha: 0.1)),
                boxShadow: [BoxShadow(color: Brand.violet.withValues(alpha: 0.18), blurRadius: 40, offset: const Offset(0, 18))],
              ),
              child: Column(
                children: [
                  EventBanner(
                    theme: e.theme,
                    height: 130,
                    radius: 26,
                    squareBottom: true,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(ticket.type.toUpperCase(), style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 2, color: Colors.white70)),
                            const Spacer(),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                              decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.35), borderRadius: BorderRadius.circular(99)),
                              child: Text(ticket.status.toUpperCase(), style: TextStyle(color: statusColor, fontSize: 11, fontWeight: FontWeight.w800, letterSpacing: 0.8)),
                            ),
                          ],
                        ),
                        const Spacer(),
                        Text(e.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, height: 1.05)),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(22, 18, 22, 8),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _field('Date and time', fmtDateTime(e.startsAt)),
                        const SizedBox(height: 12),
                        _field('Venue', '${e.venue}, ${e.city}'),
                      ],
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 22, vertical: 14),
                    child: LayoutBuilder(
                      builder: (context, c) => Row(
                        children: List.generate(
                          (c.maxWidth / 12).floor(),
                          (_) => Expanded(child: Container(height: 2, margin: const EdgeInsets.symmetric(horizontal: 2), color: Colors.white.withValues(alpha: 0.14))),
                        ),
                      ),
                    ),
                  ),
                  Padding(
                    padding: const EdgeInsets.fromLTRB(22, 4, 22, 26),
                    child: Column(
                      children: [
                        Opacity(
                          opacity: ticket.valid ? 1 : 0.4,
                          child: Container(
                            padding: const EdgeInsets.all(14),
                            decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(20)),
                            child: QrImageView(data: ticket.qr, size: 230, padding: EdgeInsets.zero, backgroundColor: Colors.white),
                          ),
                        ),
                        const SizedBox(height: 16),
                        Text(ticket.shortCode, style: const TextStyle(fontFamily: 'monospace', fontSize: 22, fontWeight: FontWeight.w800, letterSpacing: 5)),
                        const SizedBox(height: 8),
                        Text(
                          ticket.status == 'used' && ticket.checkedInAt != null
                              ? 'Checked in ${fmtDateTime(ticket.checkedInAt!)}'
                              : 'Show this code at the entrance. Turn your screen brightness up for faster scanning.',
                          textAlign: TextAlign.center,
                          style: const TextStyle(color: Brand.ink300, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _field(String label, String value) => Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(), style: const TextStyle(color: Brand.ink300, fontSize: 10.5, fontWeight: FontWeight.w800, letterSpacing: 1.4)),
          const SizedBox(height: 2),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15.5)),
        ],
      );
}
