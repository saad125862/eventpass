import 'dart:async';

import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';
import 'event_detail_screen.dart';

const eventCategories = ['Music', 'Tech', 'Art', 'Food', 'Sports', 'Business', 'Comedy', 'Community'];

/// Opens a URL in the browser, with a friendly message if it fails.
Future<void> openUrl(BuildContext context, String url) async {
  final ok = await launchUrl(Uri.parse(url), mode: LaunchMode.externalApplication);
  if (!ok && context.mounted) {
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Could not open the link.')));
  }
}

class EventsScreen extends StatefulWidget {
  const EventsScreen({super.key});

  @override
  State<EventsScreen> createState() => _EventsScreenState();
}

class _EventsScreenState extends State<EventsScreen> {
  final _search = TextEditingController();
  Timer? _debounce;
  String? _category;
  late Future<List<EventItem>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _search.dispose();
    super.dispose();
  }

  Future<List<EventItem>> _load() => Api.instance.events(search: _search.text, category: _category);

  void _reload() => setState(() => _future = _load());

  void _onSearchChanged(String _) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), _reload);
  }

  @override
  Widget build(BuildContext context) {
    return SafeArea(
      child: RefreshIndicator(
        color: Brand.violetLight,
        backgroundColor: Brand.ink700,
        onRefresh: () async {
          _reload();
          await _future.catchError((_) => <EventItem>[]);
        },
        child: CustomScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(20, 20, 20, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Discover', style: TextStyle(color: Brand.violetLight, fontSize: 12, fontWeight: FontWeight.w800, letterSpacing: 2)),
                    const SizedBox(height: 4),
                    const Text('Find your next\nnight out', style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, height: 1.05, letterSpacing: -0.8)),
                    const SizedBox(height: 18),
                    TextField(
                      controller: _search,
                      onChanged: _onSearchChanged,
                      textInputAction: TextInputAction.search,
                      decoration: const InputDecoration(hintText: 'Search events or cities', prefixIcon: Icon(Icons.search, color: Brand.ink300)),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      height: 38,
                      child: ListView(
                        scrollDirection: Axis.horizontal,
                        children: [
                          _chip('All', _category == null, () {
                            _category = null;
                            _reload();
                          }),
                          for (final c in eventCategories)
                            _chip(c, _category == c, () {
                              _category = c;
                              _reload();
                            }),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            FutureBuilder<List<EventItem>>(
              future: _future,
              builder: (context, snap) {
                if (snap.connectionState != ConnectionState.done) {
                  return const SliverFillRemaining(
                    hasScrollBody: false,
                    child: Center(child: Padding(padding: EdgeInsets.all(40), child: CircularProgressIndicator())),
                  );
                }
                if (snap.hasError) {
                  return SliverFillRemaining(hasScrollBody: false, child: _ErrorState(message: '${snap.error}', onRetry: _reload));
                }
                final events = snap.data!;
                if (events.isEmpty) {
                  return const SliverFillRemaining(
                    hasScrollBody: false,
                    child: _EmptyState(icon: Icons.search_off, title: 'Nothing matches yet', subtitle: 'Try another search or category.'),
                  );
                }
                return SliverPadding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
                  sliver: SliverList.separated(
                    itemCount: events.length,
                    separatorBuilder: (_, _) => const SizedBox(height: 16),
                    itemBuilder: (context, i) => FadeSlideIn(delayMs: (i * 60).clamp(0, 360), child: _EventCard(event: events[i])),
                  ),
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _chip(String label, bool selected, VoidCallback onTap) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
        showCheckmark: false,
        labelStyle: TextStyle(fontWeight: FontWeight.w600, color: selected ? Colors.white : Brand.ink200),
        backgroundColor: Colors.white.withValues(alpha: 0.05),
        selectedColor: Brand.violet.withValues(alpha: 0.3),
        side: BorderSide(color: selected ? Brand.violetLight : Colors.white.withValues(alpha: 0.1)),
        shape: const StadiumBorder(),
      ),
    );
  }
}

class _EventCard extends StatelessWidget {
  const _EventCard({required this.event});

  final EventItem event;

  @override
  Widget build(BuildContext context) {
    final min = event.minPriceCents;
    return Material(
      color: Brand.ink800,
      borderRadius: BorderRadius.circular(22),
      child: InkWell(
        borderRadius: BorderRadius.circular(22),
        onTap: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => EventDetailScreen(slug: event.slug, preview: event))),
        child: Container(
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(22), border: Border.all(color: Colors.white.withValues(alpha: 0.08))),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Hero(
                tag: 'banner-${event.slug}',
                child: EventBanner(
                  theme: event.theme,
                  height: 130,
                  radius: 22,
                  squareBottom: true,
                  child: Row(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _pill(event.category),
                      const Spacer(),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                        decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.92), borderRadius: BorderRadius.circular(12)),
                        child: Column(
                          children: [
                            Text(monthAbbr(event.startsAt), style: const TextStyle(color: Brand.ink900, fontSize: 10, fontWeight: FontWeight.w800, letterSpacing: 1.4)),
                            Text('${event.startsAt.toLocal().day}', style: const TextStyle(color: Brand.ink900, fontSize: 20, fontWeight: FontWeight.w800, height: 1.1)),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 14, 16, 16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(event.title, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800, height: 1.15)),
                    const SizedBox(height: 10),
                    _meta(Icons.calendar_today_outlined, fmtDateTime(event.startsAt)),
                    const SizedBox(height: 4),
                    _meta(Icons.place_outlined, '${event.venue}, ${event.city}'),
                    const SizedBox(height: 12),
                    Row(
                      children: [
                        Text(min == null ? 'No tickets yet' : (min == 0 ? 'Free' : 'From ${fmtMoney(min)}'), style: const TextStyle(fontWeight: FontWeight.w800)),
                        const Spacer(),
                        if (event.soldOut)
                          _status('Sold out', Brand.ink300)
                        else if (event.almostGone)
                          _status('Almost gone · ${event.remaining} left', Brand.sun),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _pill(String text) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(color: Colors.black.withValues(alpha: 0.3), borderRadius: BorderRadius.circular(99)),
        child: Text(text, style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w700)),
      );

  Widget _meta(IconData icon, String text) => Row(
        children: [
          Icon(icon, size: 15, color: Brand.ink300),
          const SizedBox(width: 8),
          Expanded(child: Text(text, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: Brand.ink200, fontSize: 13.5))),
        ],
      );

  Widget _status(String text, Color color) => Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
        decoration: BoxDecoration(color: color.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(99)),
        child: Text(text, style: TextStyle(color: color, fontSize: 11.5, fontWeight: FontWeight.w800)),
      );
}

class _EmptyState extends StatelessWidget {
  const _EmptyState({required this.icon, required this.title, required this.subtitle});

  final IconData icon;
  final String title;
  final String subtitle;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(40),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(icon, size: 44, color: Brand.ink300),
              const SizedBox(height: 14),
              Text(title, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w800)),
              const SizedBox(height: 6),
              Text(subtitle, textAlign: TextAlign.center, style: const TextStyle(color: Brand.ink300)),
            ],
          ),
        ),
      );
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) => Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.cloud_off_outlined, size: 44, color: Brand.ink300),
              const SizedBox(height: 14),
              Text(message, textAlign: TextAlign.center, style: const TextStyle(color: Brand.ink200)),
              const SizedBox(height: 18),
              FilledButton.tonal(onPressed: onRetry, child: const Text('Try again')),
            ],
          ),
        ),
      );
}
