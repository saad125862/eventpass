import 'package:flutter/material.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';
import 'events_screen.dart';

class EventDetailScreen extends StatefulWidget {
  const EventDetailScreen({super.key, required this.slug, required this.preview});

  final String slug;
  final EventItem preview;

  @override
  State<EventDetailScreen> createState() => _EventDetailScreenState();
}

class _EventDetailScreenState extends State<EventDetailScreen> {
  late final Future<EventItem> _future = Api.instance.event(widget.slug);

  @override
  Widget build(BuildContext context) {
    final preview = widget.preview;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            pinned: true,
            expandedHeight: 220,
            backgroundColor: Brand.ink950,
            leading: Padding(
              padding: const EdgeInsets.all(8),
              child: CircleAvatar(
                backgroundColor: Colors.black.withValues(alpha: 0.35),
                child: IconButton(icon: const Icon(Icons.arrow_back, color: Colors.white), onPressed: () => Navigator.of(context).pop()),
              ),
            ),
            flexibleSpace: FlexibleSpaceBar(
              background: Hero(
                tag: 'banner-${preview.slug}',
                child: EventBanner(
                  theme: preview.theme,
                  height: 260,
                  radius: 0,
                  child: Align(
                    alignment: Alignment.bottomLeft,
                    child: Padding(
                      padding: const EdgeInsets.only(bottom: 14),
                      child: Text(preview.title, maxLines: 3, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 28, fontWeight: FontWeight.w800, height: 1.05, letterSpacing: -0.6)),
                    ),
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.fromLTRB(20, 18, 20, 32),
              child: FutureBuilder<EventItem>(
                future: _future,
                builder: (context, snap) {
                  final e = snap.data ?? preview;
                  final loading = snap.connectionState != ConnectionState.done;
                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _info(Icons.calendar_today_outlined, 'Date and time', fmtDateTime(e.startsAt)),
                      const SizedBox(height: 12),
                      _info(Icons.place_outlined, 'Location', '${e.venue}, ${e.city}'),
                      if (e.organizer != null) ...[const SizedBox(height: 12), _info(Icons.person_outline, 'Hosted by', e.organizer!)],
                      const SizedBox(height: 24),
                      if (loading)
                        const Center(child: Padding(padding: EdgeInsets.all(24), child: CircularProgressIndicator()))
                      else if (snap.hasError)
                        Text('${snap.error}', style: const TextStyle(color: Brand.flame))
                      else ...[
                        if ((e.description ?? '').isNotEmpty) ...[
                          const Text('About', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                          const SizedBox(height: 10),
                          Text(e.description!, style: const TextStyle(color: Brand.ink200, height: 1.5, fontSize: 15)),
                          const SizedBox(height: 26),
                        ],
                        const Text('Tickets', style: TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
                        const SizedBox(height: 12),
                        for (final t in e.ticketTypes) _ticketRow(t),
                        const SizedBox(height: 22),
                        GradientButton(
                          label: 'Buy on the website',
                          icon: Icons.open_in_new,
                          onPressed: () => openUrl(context, '${Api.baseUrl}/events/${e.slug}'),
                        ),
                        const SizedBox(height: 10),
                        const Center(
                          child: Text('Checkout runs on the website. Your tickets then appear here.', style: TextStyle(color: Brand.ink300, fontSize: 12.5)),
                        ),
                      ],
                    ],
                  );
                },
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _info(IconData icon, String label, String value) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(color: Brand.ink800, borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.white.withValues(alpha: 0.07))),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(color: Brand.violet.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(12)),
            child: Icon(icon, color: Brand.violetLight, size: 20),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(label.toUpperCase(), style: const TextStyle(color: Brand.ink300, fontSize: 10.5, fontWeight: FontWeight.w800, letterSpacing: 1.2)),
                const SizedBox(height: 2),
                Text(value, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _ticketRow(TicketType t) {
    final soldOut = t.available == 0;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(color: Brand.ink800, borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.white.withValues(alpha: 0.07))),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(t.name, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                if ((t.description ?? '').isNotEmpty) Text(t.description!, style: const TextStyle(color: Brand.ink300, fontSize: 12.5)),
                const SizedBox(height: 6),
                Text(
                  soldOut ? 'Sold out' : (t.available <= 10 ? 'Only ${t.available} left' : '${t.available} available'),
                  style: TextStyle(color: soldOut ? Brand.ink300 : (t.available <= 10 ? Brand.sun : Brand.ink300), fontSize: 12.5, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
          Text(fmtMoney(t.priceCents), style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800)),
        ],
      ),
    );
  }
}
