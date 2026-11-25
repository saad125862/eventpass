import 'package:flutter/material.dart';

/// Brand colours, mirrored from the EventPass website (resources/css/app.css).
class Brand {
  static const ink950 = Color(0xFF06060C);
  static const ink900 = Color(0xFF0B0B14);
  static const ink800 = Color(0xFF11111D);
  static const ink700 = Color(0xFF181829);
  static const ink600 = Color(0xFF23233A);
  static const ink300 = Color(0xFF8D8DAB);
  static const ink200 = Color(0xFFB9B9D1);
  static const violet = Color(0xFF7C5CFF);
  static const violetLight = Color(0xFF9A82FF);
  static const flame = Color(0xFFFF4D8D);
  static const sun = Color(0xFFFFB84D);
  static const mint = Color(0xFF34D399);

  /// Event artwork themes (same six gradient pairs as the web `.theme-N` classes).
  static const _themes = <List<Color>>[
    [Color(0xFF7C5CFF), Color(0xFFFF4D8D)],
    [Color(0xFF06B6D4), Color(0xFF7C5CFF)],
    [Color(0xFFFF4D8D), Color(0xFFFFB84D)],
    [Color(0xFF10B981), Color(0xFF06B6D4)],
    [Color(0xFFF97316), Color(0xFFEF4444)],
    [Color(0xFF8B5CF6), Color(0xFF22D3EE)],
  ];

  static List<Color> theme(int index) => _themes[index % _themes.length];

  static const brandGradient = LinearGradient(
    colors: [violet, flame],
    begin: Alignment.centerLeft,
    end: Alignment.centerRight,
  );
}

ThemeData buildTheme() {
  const scheme = ColorScheme.dark(
    primary: Brand.violet,
    secondary: Brand.flame,
    surface: Brand.ink800,
    onSurface: Colors.white,
  );

  return ThemeData(
    useMaterial3: true,
    colorScheme: scheme,
    scaffoldBackgroundColor: Brand.ink950,
    appBarTheme: const AppBarTheme(
      backgroundColor: Brand.ink950,
      surfaceTintColor: Colors.transparent,
      elevation: 0,
      centerTitle: false,
    ),
    navigationBarTheme: NavigationBarThemeData(
      backgroundColor: Brand.ink900,
      indicatorColor: Brand.violet.withValues(alpha: 0.25),
      surfaceTintColor: Colors.transparent,
      labelTextStyle: WidgetStateProperty.all(
        const TextStyle(fontSize: 12, fontWeight: FontWeight.w600),
      ),
    ),
    inputDecorationTheme: InputDecorationTheme(
      filled: true,
      fillColor: Brand.ink900,
      contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
      hintStyle: const TextStyle(color: Brand.ink300),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.1)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: BorderSide(color: Colors.white.withValues(alpha: 0.1)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(14),
        borderSide: const BorderSide(color: Brand.violetLight, width: 1.5),
      ),
    ),
    snackBarTheme: SnackBarThemeData(
      backgroundColor: Brand.ink700,
      behavior: SnackBarBehavior.floating,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
    ),
  );
}

const _weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
const _months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/// "Sat, Dec 6 · 9:00 AM" (no intl dependency needed).
String fmtDateTime(DateTime d) {
  final local = d.toLocal();
  final hour12 = local.hour % 12 == 0 ? 12 : local.hour % 12;
  final minute = local.minute.toString().padLeft(2, '0');
  final ampm = local.hour < 12 ? 'AM' : 'PM';
  return '${_weekdays[local.weekday - 1]}, ${_months[local.month - 1]} ${local.day} · $hour12:$minute $ampm';
}

String monthAbbr(DateTime d) => _months[d.toLocal().month - 1].toUpperCase();

/// 2500 -> "$25.00", 0 -> "Free".
String fmtMoney(int cents) => cents == 0 ? 'Free' : '\$${(cents / 100).toStringAsFixed(2)}';

/// A rounded, gradient "event artwork" banner used on cards and detail pages.
class EventBanner extends StatelessWidget {
  const EventBanner({super.key, required this.theme, this.height = 120, this.child, this.radius = 20, this.squareBottom = false});

  final int theme;
  final double height;
  final double radius;
  final Widget? child;

  /// Use when the banner sits on top of a card, so only the top corners are rounded.
  final bool squareBottom;

  @override
  Widget build(BuildContext context) {
    final colors = Brand.theme(theme);
    return Container(
      height: height,
      width: double.infinity,
      decoration: BoxDecoration(
        borderRadius: BorderRadius.vertical(
          top: Radius.circular(radius),
          bottom: squareBottom ? Radius.zero : Radius.circular(radius),
        ),
        color: Brand.ink800,
        gradient: RadialGradient(
          center: Alignment.topLeft,
          radius: 1.6,
          colors: [colors[0], colors[1].withValues(alpha: 0.85), Brand.ink800],
          stops: const [0.0, 0.55, 1.0],
        ),
      ),
      padding: const EdgeInsets.all(14),
      child: child,
    );
  }
}

/// Gradient call-to-action button.
class GradientButton extends StatelessWidget {
  const GradientButton({super.key, required this.label, required this.onPressed, this.icon, this.busy = false});

  final String label;
  final VoidCallback? onPressed;
  final IconData? icon;
  final bool busy;

  @override
  Widget build(BuildContext context) {
    final enabled = onPressed != null && !busy;
    return Opacity(
      opacity: enabled ? 1 : 0.55,
      child: Material(
        color: Colors.transparent,
        child: Ink(
          decoration: BoxDecoration(
            gradient: Brand.brandGradient,
            borderRadius: BorderRadius.circular(14),
            boxShadow: [BoxShadow(color: Brand.violet.withValues(alpha: 0.35), blurRadius: 18, offset: const Offset(0, 6))],
          ),
          child: InkWell(
            borderRadius: BorderRadius.circular(14),
            onTap: enabled ? onPressed : null,
            child: Padding(
              padding: const EdgeInsets.symmetric(vertical: 16, horizontal: 20),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  if (busy)
                    const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2.4, color: Colors.white))
                  else ...[
                    if (icon != null) ...[Icon(icon, size: 18), const SizedBox(width: 8)],
                    Text(label, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 15)),
                  ],
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }
}

/// Fades and slides a child in, with an optional stagger delay.
class FadeSlideIn extends StatelessWidget {
  const FadeSlideIn({super.key, required this.child, this.delayMs = 0});

  final Widget child;
  final int delayMs;

  @override
  Widget build(BuildContext context) {
    return TweenAnimationBuilder<double>(
      tween: Tween(begin: 0, end: 1),
      duration: Duration(milliseconds: 450 + delayMs),
      curve: Curves.easeOutCubic,
      builder: (context, t, child) => Opacity(
        opacity: t,
        child: Transform.translate(offset: Offset(0, (1 - t) * 18), child: child),
      ),
      child: child,
    );
  }
}
