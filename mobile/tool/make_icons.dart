// Generates the launcher icon PNGs (run with: flutter test tool/make_icons.dart).
// Drawn in code so the icon always matches the brand and needs no design file.
import 'dart:io';
import 'dart:ui' as ui;

import 'package:flutter/material.dart';
import 'package:flutter/rendering.dart';
import 'package:flutter_test/flutter_test.dart';

class _IconPainter extends CustomPainter {
  _IconPainter({required this.background});

  final bool background;

  @override
  void paint(Canvas canvas, Size size) {
    final s = size.width;

    if (background) {
      final rect = Offset.zero & size;
      canvas.drawRect(
        rect,
        Paint()
          ..shader = const LinearGradient(
            colors: [Color(0xFF7C5CFF), Color(0xFFFF4D8D)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ).createShader(rect),
      );
    }

    // Ticket stays inside the adaptive-icon safe zone (centre ~60%).
    final w = s * 0.52;
    final h = s * 0.34;
    final left = (s - w) / 2;
    final top = (s - h) / 2;
    final body = Path()..addRRect(RRect.fromRectAndRadius(Rect.fromLTWH(left, top, w, h), Radius.circular(s * 0.05)));
    final notches = Path()
      ..addOval(Rect.fromCircle(center: Offset(left, top + h / 2), radius: s * 0.052))
      ..addOval(Rect.fromCircle(center: Offset(left + w, top + h / 2), radius: s * 0.052));
    final ticket = Path.combine(PathOperation.difference, body, notches);

    // Soft shadow, then the white ticket.
    canvas.drawPath(ticket.shift(Offset(0, s * 0.012)), Paint()..color = const Color(0x33000000)..maskFilter = MaskFilter.blur(BlurStyle.normal, s * 0.012));
    canvas.drawPath(ticket, Paint()..color = Colors.white);

    // Perforation line and a small star on the stub.
    final dashX = left + w * 0.70;
    final dash = Paint()..color = const Color(0xFF7C5CFF).withValues(alpha: 0.55);
    for (var i = 0; i < 6; i++) {
      final y = top + h * 0.12 + i * (h * 0.76 / 6);
      canvas.drawRRect(RRect.fromRectAndRadius(Rect.fromLTWH(dashX - s * 0.004, y, s * 0.008, h * 0.76 / 6 * 0.55), Radius.circular(s * 0.004)), dash);
    }

    final bars = Paint()..color = const Color(0xFF7C5CFF);
    canvas.drawRRect(RRect.fromRectAndRadius(Rect.fromLTWH(left + w * 0.10, top + h * 0.28, w * 0.40, h * 0.12), Radius.circular(s * 0.01)), bars);
    canvas.drawRRect(
      RRect.fromRectAndRadius(Rect.fromLTWH(left + w * 0.10, top + h * 0.50, w * 0.28, h * 0.10), Radius.circular(s * 0.01)),
      Paint()..color = const Color(0xFFFF4D8D),
    );

    final star = Path();
    final c = Offset(left + w * 0.84, top + h / 2);
    final r = h * 0.15;
    for (var i = 0; i < 10; i++) {
      final radius = i.isEven ? r : r * 0.45;
      final a = -3.14159265 / 2 + i * 3.14159265 / 5;
      final p = Offset(c.dx + radius * _cos(a), c.dy + radius * _sin(a));
      i == 0 ? star.moveTo(p.dx, p.dy) : star.lineTo(p.dx, p.dy);
    }
    star.close();
    canvas.drawPath(star, Paint()..color = const Color(0xFFFF4D8D));
  }

  double _cos(double a) => _approx(a + 3.14159265 / 2);
  double _sin(double a) => _approx(a);

  // Small Taylor-free helper using dart:math would be simpler, but keeps this file dependency-light.
  double _approx(double x) {
    // sin(x) via range reduction + series (accurate enough for drawing).
    const twoPi = 6.28318530718;
    x = x % twoPi;
    if (x > 3.14159265) x -= twoPi;
    final x2 = x * x;
    return x * (1 - x2 / 6 * (1 - x2 / 20 * (1 - x2 / 42 * (1 - x2 / 72 * (1 - x2 / 110)))));
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

void main() {
  testWidgets('render launcher icons', (tester) async {
    tester.view.physicalSize = const Size(1100, 1100);
    tester.view.devicePixelRatio = 1.0;
    addTearDown(tester.view.reset);

    Future<void> render(String path, {required bool background}) async {
      final key = GlobalKey();
      await tester.pumpWidget(
        Directionality(
          textDirection: TextDirection.ltr,
          child: Center(
            child: RepaintBoundary(
              key: key,
              child: SizedBox(width: 1024, height: 1024, child: CustomPaint(painter: _IconPainter(background: background))),
            ),
          ),
        ),
      );
      await tester.runAsync(() async {
        final boundary = key.currentContext!.findRenderObject()! as RenderRepaintBoundary;
        final image = await boundary.toImage(pixelRatio: 1.0);
        final data = await image.toByteData(format: ui.ImageByteFormat.png);
        final file = File(path)..createSync(recursive: true);
        file.writeAsBytesSync(data!.buffer.asUint8List());
      });
    }

    await render('assets/icon/icon.png', background: true);
    await render('assets/icon/icon_foreground.png', background: false);
  });
}
