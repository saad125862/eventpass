import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../api.dart';
import '../models.dart';
import '../theme.dart';

/// Organizer tool: pick an event, then scan ticket QR codes with the camera.
class ScanScreen extends StatefulWidget {
  const ScanScreen({super.key});

  @override
  State<ScanScreen> createState() => _ScanScreenState();
}

class _ScanScreenState extends State<ScanScreen> {
  late Future<List<OrganizerEvent>> _events = Api.instance.organizerEvents();
  OrganizerEvent? _selected;

  @override
  Widget build(BuildContext context) {
    final event = _selected;
    if (event != null) {
      return _Scanner(event: event, onChangeEvent: () => setState(() => _selected = null));
    }

    return SafeArea(
      child: RefreshIndicator(
        color: Brand.violetLight,
        backgroundColor: Brand.ink700,
        onRefresh: () async {
          setState(() => _events = Api.instance.organizerEvents());
          await _events.catchError((_) => <OrganizerEvent>[]);
        },
        child: ListView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(20, 20, 20, 24),
          children: [
            const Text('Check-in', style: TextStyle(color: Brand.violetLight, fontSize: 12, fontWeight: FontWeight.w800, letterSpacing: 2)),
            const SizedBox(height: 4),
            const Text('Pick an event\nto scan', style: TextStyle(fontSize: 32, fontWeight: FontWeight.w800, height: 1.05, letterSpacing: -0.8)),
            const SizedBox(height: 20),
            FutureBuilder<List<OrganizerEvent>>(
              future: _events,
              builder: (context, snap) {
                if (snap.connectionState != ConnectionState.done) {
                  return const Padding(padding: EdgeInsets.all(40), child: Center(child: CircularProgressIndicator()));
                }
                if (snap.hasError) {
                  return Column(
                    children: [
                      Text('${snap.error}', textAlign: TextAlign.center, style: const TextStyle(color: Brand.ink200)),
                      const SizedBox(height: 14),
                      FilledButton.tonal(onPressed: () => setState(() => _events = Api.instance.organizerEvents()), child: const Text('Try again')),
                    ],
                  );
                }
                final events = snap.data!;
                if (events.isEmpty) {
                  return const Padding(
                    padding: EdgeInsets.all(32),
                    child: Text('You have no events yet. Create one on the website first.', textAlign: TextAlign.center, style: TextStyle(color: Brand.ink300)),
                  );
                }
                return Column(
                  children: [
                    for (var i = 0; i < events.length; i++)
                      FadeSlideIn(delayMs: i * 60, child: _eventTile(events[i])),
                  ],
                );
              },
            ),
          ],
        ),
      ),
    );
  }

  Widget _eventTile(OrganizerEvent e) {
    final pct = e.counts.total == 0 ? 0.0 : e.counts.checkedIn / e.counts.total;
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Material(
        color: Brand.ink800,
        borderRadius: BorderRadius.circular(20),
        child: InkWell(
          borderRadius: BorderRadius.circular(20),
          onTap: () => setState(() => _selected = e),
          child: Container(
            padding: const EdgeInsets.all(14),
            decoration: BoxDecoration(borderRadius: BorderRadius.circular(20), border: Border.all(color: Colors.white.withValues(alpha: 0.08))),
            child: Row(
              children: [
                SizedBox(width: 56, height: 56, child: EventBanner(theme: e.theme, radius: 14, height: 56)),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(e.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontWeight: FontWeight.w800, fontSize: 16)),
                      const SizedBox(height: 2),
                      Text(fmtDateTime(e.startsAt), style: const TextStyle(color: Brand.ink300, fontSize: 12.5)),
                      const SizedBox(height: 8),
                      ClipRRect(
                        borderRadius: BorderRadius.circular(99),
                        child: LinearProgressIndicator(value: pct, minHeight: 6, backgroundColor: Colors.white.withValues(alpha: 0.08), color: Brand.mint),
                      ),
                      const SizedBox(height: 4),
                      Text('${e.counts.checkedIn} / ${e.counts.total} checked in', style: const TextStyle(color: Brand.ink300, fontSize: 12)),
                    ],
                  ),
                ),
                const SizedBox(width: 8),
                const Icon(Icons.qr_code_scanner, color: Brand.violetLight),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _Scanner extends StatefulWidget {
  const _Scanner({required this.event, required this.onChangeEvent});

  final OrganizerEvent event;
  final VoidCallback onChangeEvent;

  @override
  State<_Scanner> createState() => _ScannerState();
}

class _ScannerState extends State<_Scanner> {
  final _controller = MobileScannerController(formats: const [BarcodeFormat.qrCode]);
  final _manual = TextEditingController();
  late CheckInCounts _counts = widget.event.counts;
  ScanResult? _result;
  String? _error;
  bool _busy = false;
  String? _last;
  final _history = <ScanResult>[];

  @override
  void dispose() {
    _controller.dispose();
    _manual.dispose();
    super.dispose();
  }

  Future<void> _submit(String raw) async {
    final code = raw.trim().split('/').last;
    if (code.isEmpty || _busy || code == _last) return;
    _busy = true;
    _last = code;

    try {
      final r = await Api.instance.checkIn(widget.event.slug, code);
      HapticFeedback.mediumImpact();
      if (!mounted) return;
      setState(() {
        _result = r;
        _error = null;
        _counts = r.counts;
        _history.insert(0, r);
        if (_history.length > 6) _history.removeLast();
      });
    } on ApiException catch (e) {
      if (!mounted) return;
      setState(() {
        _error = e.message;
        _result = null;
      });
    } finally {
      _manual.clear();
      // Brief cool-down so one QR code is not submitted repeatedly while it stays in view.
      Future.delayed(const Duration(milliseconds: 1800), () {
        _busy = false;
        _last = null;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final r = _result;
    final tone = _error != null ? Brand.flame : (r == null ? Brand.ink300 : (r.ok ? Brand.mint : (r.warn ? Brand.sun : Brand.flame)));
    final title = _error != null ? 'Could not check in' : (r?.message ?? 'Point the camera at a ticket');
    final detail = _error ?? r?.detail ?? 'The QR code is read automatically.';

    return SafeArea(
      child: ListView(
        padding: const EdgeInsets.fromLTRB(20, 12, 20, 24),
        children: [
          Row(
            children: [
              IconButton(onPressed: widget.onChangeEvent, icon: const Icon(Icons.arrow_back)),
              Expanded(child: Text(widget.event.title, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800))),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.06), borderRadius: BorderRadius.circular(14)),
                child: Text('${_counts.checkedIn} / ${_counts.total}', style: const TextStyle(fontWeight: FontWeight.w800)),
              ),
            ],
          ),
          const SizedBox(height: 14),
          ClipRRect(
            borderRadius: BorderRadius.circular(24),
            child: AspectRatio(
              aspectRatio: 1,
              child: Stack(
                fit: StackFit.expand,
                children: [
                  MobileScanner(
                    controller: _controller,
                    onDetect: (capture) {
                      final value = capture.barcodes.isEmpty ? null : capture.barcodes.first.rawValue;
                      if (value != null) _submit(value);
                    },
                    errorBuilder: (context, error) => Container(
                      color: Brand.ink900,
                      alignment: Alignment.center,
                      padding: const EdgeInsets.all(24),
                      child: const Text('Camera unavailable. Allow camera access, or type the code below.', textAlign: TextAlign.center, style: TextStyle(color: Brand.ink300)),
                    ),
                  ),
                  IgnorePointer(
                    child: Container(
                      margin: const EdgeInsets.all(34),
                      decoration: BoxDecoration(borderRadius: BorderRadius.circular(22), border: Border.all(color: Brand.violetLight.withValues(alpha: 0.8), width: 2.5)),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          AnimatedContainer(
            duration: const Duration(milliseconds: 250),
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: tone.withValues(alpha: 0.12),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: tone.withValues(alpha: 0.45)),
            ),
            child: AnimatedSwitcher(
              duration: const Duration(milliseconds: 250),
              child: Row(
                key: ValueKey('$title|$detail'),
                children: [
                  Icon(_error != null ? Icons.error_outline : (r == null ? Icons.qr_code_2 : (r.ok ? Icons.check_circle : (r.warn ? Icons.warning_amber_rounded : Icons.cancel))), color: tone, size: 34),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(title, style: const TextStyle(fontSize: 19, fontWeight: FontWeight.w800)),
                        const SizedBox(height: 2),
                        Text(detail, style: const TextStyle(color: Brand.ink200, fontSize: 13.5)),
                      ],
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(height: 16),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _manual,
                  autocorrect: false,
                  textInputAction: TextInputAction.done,
                  onSubmitted: _submit,
                  decoration: const InputDecoration(hintText: 'Enter ticket code manually'),
                ),
              ),
              const SizedBox(width: 10),
              FilledButton.tonal(
                style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 18, horizontal: 18)),
                onPressed: () => _submit(_manual.text),
                child: const Text('Check'),
              ),
            ],
          ),
          if (_history.isNotEmpty) ...[
            const SizedBox(height: 22),
            const Text('Recent scans', style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15)),
            const SizedBox(height: 8),
            for (final h in _history)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 5),
                child: Row(
                  children: [
                    Container(width: 9, height: 9, decoration: BoxDecoration(shape: BoxShape.circle, color: h.ok ? Brand.mint : (h.warn ? Brand.sun : Brand.flame))),
                    const SizedBox(width: 10),
                    Expanded(child: Text(h.message, maxLines: 1, overflow: TextOverflow.ellipsis)),
                  ],
                ),
              ),
          ],
        ],
      ),
    );
  }
}
