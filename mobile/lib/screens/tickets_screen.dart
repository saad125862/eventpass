import 'package:flutter/material.dart';
import 'package:qr_flutter/qr_flutter.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';
import 'ticket_pass_screen.dart';

class TicketsScreen extends StatefulWidget {
  const TicketsScreen({super.key});

  @override
  State<TicketsScreen> createState() => _TicketsScreenState();
}

class _TicketsScreenState extends State<TicketsScreen> {
  late Future<List<Ticket>> _future = Api.instance.tickets();

  void _reload() => setState(() => _future = Api.instance.tickets());

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: RefreshIndicator(
        color: Brand.violetLight,
        backgroundColor: Brand.ink700,
        onRefresh: () async {
          _reload();
          await _future.catchError((_) => <Ticket>[]);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            const SliverToBoxAdapter(
              child: Padding(
                padding: EdgeInsets.fromLTRB(20, 20, 20, 12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('Wallet', style: TextStyle(color: Brand.violetLight, fontSize: 12, fontWeight: FontWeight.w800, letterSpacing: 2)),
                    SizedBox(height: 4),
                    Text('My tickets', style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, letterSpacing: -0.8)),
                  ],
                ),
              ),
            ),
            FutureBuilder<List<Ticket>>(
              future: _future,
              builder: (context, snap) {
                if (snap.connectionState != ConnectionState.done) {
                  return const SliverFillRemaining(hasScrollBody: false, child: Center(child: CircularProgressIndicator()));
                }
                if (snap.hasError) {
                  return SliverFillRemaining(
                    hasScrollBody: false,
                    child: Center(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.cloud_off_outlined, size: 44, color: Brand.ink300),
                            const SizedBox(height: 14),
                            Text('${snap.error}', textAlign: TextAlign.center, style: const TextStyle(color: Brand.ink200)),
                            const SizedBox(height: 18),
                            FilledButton.tonal(onPressed: _reload, child: const Text('Try again')),
                          ],
                        ),
                      ),
                    ),
                  );
                }
                final tickets = snap.data!;
                if (tickets.isEmpty) {
                  return const SliverFillRemaining(
                    hasScrollBody: false,
                    child: Center(
                      child: Padding(
                        padding: EdgeInsets.all(40),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.confirmation_number_outlined, size: 48, color: Brand.ink300),
                            SizedBox(height: 14),
                            Text('No tickets yet', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                            SizedBox(height: 6),
                            Text('Book an event on the website and your QR passes will show up here.', textAlign: TextAlign.center, style: TextStyle(color: Brand.ink300)),
                          ],
                        ),
                      ),
                    ),
                  );
                }
                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 4, 20, 24),
                  sliver: SliverList.separated(
                    itemCount: tickets.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 16),
                    itemBuilder: (context, i) => FadeSlideIn(delayMs: (i * 60).clamp(0, 360), child: _TicketCard(ticket: tickets[i])),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }
}

class _TicketCard extends StatelessWidget {
  const _TicketCard({required this.ticket});

  final Ticket ticket;

  @override
  Widget build(BuildContext context) {
    final used = ticket.status == 'used';
    final statusColor = ticket.valid ? Brand.mint : (used ? Brand.violetLight : Brand.flame);

    return Opacity(
      opacity: ticket.valid ? 1 : 0.7,
      child: Material(
        color: Brand.ink800,
        borderRadius: BorderRadius.circular(22),
        child: InkWell(
          borderRadius: BorderRadius.circular(22),
          onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => TicketPassScreen(ticket: ticket))),
          child: Container(
            decoration: BoxDecoration(borderRadius: BorderRadius.circular(22), border: Border.all(color: Colors.white.withValues(alpha: 0.08))),
            child: Column(
              children: [
                EventBanner(
                  theme: ticket.event.theme,
                  height: 92,
                  radius: 22,
                  squareBottom: true,
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(ticket.type.toUpperCase(), style: const TextStyle(fontSize: 10.5, fontWeight: FontWeight.w800, letterSpacing: 1.8, color: Colors.white70)),
                            const SizedBox(height: 4),
                            Text(ticket.event.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800, height: 1.1)),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.35), borderRadius: BorderRadius.circular(99)),
                        child: Text(ticket.status.toUpperCase(), style: TextStyle(color: statusColor, fontSize: 10.5, fontWeight: FontWeight.w800, letterSpacing: 0.8)),
                      ),
                    ],
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
                  child: Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(fmtDateTime(ticket.event.startsAt), style: const TextStyle(fontWeight: FontWeight.w700)),
                            const SizedBox(height: 4),
                            Text(ticket.event.venue, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Brand.ink300, fontSize: 13)),
                            const SizedBox(height: 10),
                            Text(ticket.shortCode, style: const TextStyle(fontFamily: 'monospace', fontWeight: FontWeight.w800, letterSpacing: 2.5, fontSize: 15)),
                            const SizedBox(height: 4),
                            const Text('Tap to open pass', style: TextStyle(color: Brand.violetLight, fontSize: 12, fontWeight: FontWeight.w600)),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(12)),
                        child: QrImageView(data: ticket.qr, size: 88, padding: EdgeInsets.zero, backgroundColor: Colors.white),
                      ),
                    ],
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
