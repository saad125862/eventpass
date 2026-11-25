import 'package:flutter/material.dart';

import '../api.dart';
import '../theme.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _email = TextEditingController();
  final _password = TextEditingController();
  bool _show = false;
  String? _busy; // 'login' | 'attendee' | 'organizer'
  String? _error;

  @override
  void dispose() {
    _email.dispose();
    _password.dispose();
    super.dispose();
  }

  Future<void> _run(String key, Future<void> Function() action) async {
    FocusScope.of(context).unfocus();
    setState(() {
      _busy = key;
      _error = null;
    });
    try {
      await action();
    } on ApiException catch (e) {
      if (mounted) setState(() => _error = e.message);
    } catch (_) {
      if (mounted) setState(() => _error = 'Something went wrong. Please try again.');
    } finally {
      if (mounted) setState(() => _busy = null);
    }
  }

  @override
  Widget build(BuildContext context) {
    final busy = _busy != null;

    return Scaffold(
      body: Stack(
        children: [
          // Soft aurora glow behind the form.
          Positioned(top: -120, left: -80, child: _glow(Brand.violet, 320)),
          Positioned(bottom: -140, right: -100, child: _glow(Brand.flame, 300)),
          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 440),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      FadeSlideIn(child: _logo()),
                      const SizedBox(height: 28),
                      const FadeSlideIn(
                        delayMs: 80,
                        child: Text('Welcome back', style: TextStyle(fontSize: 34, fontWeight: FontWeight.w800, height: 1.05, letterSpacing: -0.8)),
                      ),
                      const SizedBox(height: 8),
                      const FadeSlideIn(
                        delayMs: 120,
                        child: Text('Sign in to see your tickets and scan at the door.', style: TextStyle(color: Brand.ink300, fontSize: 15)),
                      ),
                      const SizedBox(height: 28),
                      FadeSlideIn(
                        delayMs: 160,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.stretch,
                          children: [
                            TextField(
                              controller: _email,
                              keyboardType: TextInputType.emailAddress,
                              autofillHints: const [AutofillHints.email],
                              textInputAction: TextInputAction.next,
                              decoration: const InputDecoration(hintText: 'Email', prefixIcon: Icon(Icons.mail_outline, color: Brand.ink300)),
                            ),
                            const SizedBox(height: 12),
                            TextField(
                              controller: _password,
                              obscureText: !_show,
                              autofillHints: const [AutofillHints.password],
                              onSubmitted: (_) => _signIn(),
                              decoration: InputDecoration(
                                hintText: 'Password',
                                prefixIcon: const Icon(Icons.lock_outline, color: Brand.ink300),
                                suffixIcon: IconButton(
                                  icon: Icon(_show ? Icons.visibility_off_outlined : Icons.visibility_outlined, color: Brand.ink300),
                                  onPressed: () => setState(() => _show = !_show),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      AnimatedSize(
                        duration: const Duration(milliseconds: 250),
                        child: _error == null
                            ? const SizedBox(width: double.infinity)
                            : Padding(
                                padding: const EdgeInsets.only(top: 14),
                                child: Container(
                                  padding: const EdgeInsets.all(12),
                                  decoration: BoxDecoration(
                                    color: Brand.flame.withValues(alpha: 0.12),
                                    borderRadius: BorderRadius.circular(12),
                                    border: Border.all(color: Brand.flame.withValues(alpha: 0.35)),
                                  ),
                                  child: Text(_error!, style: const TextStyle(color: Brand.flame, fontSize: 13.5)),
                                ),
                              ),
                      ),
                      const SizedBox(height: 18),
                      FadeSlideIn(
                        delayMs: 200,
                        child: GradientButton(label: 'Sign in', busy: _busy == 'login', onPressed: busy ? null : _signIn),
                      ),
                      const SizedBox(height: 28),
                      FadeSlideIn(
                        delayMs: 240,
                        child: Row(
                          children: [
                            Expanded(child: Divider(color: Colors.white.withValues(alpha: 0.1))),
                            const Padding(
                              padding: EdgeInsets.symmetric(horizontal: 12),
                              child: Text('OR TRY THE DEMO', style: TextStyle(color: Brand.ink300, fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 1.4)),
                            ),
                            Expanded(child: Divider(color: Colors.white.withValues(alpha: 0.1))),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      FadeSlideIn(
                        delayMs: 280,
                        child: Row(
                          children: [
                            Expanded(child: _demoButton('attendee', Icons.confirmation_number_outlined, 'Demo attendee', 'Tickets & events')),
                            const SizedBox(width: 12),
                            Expanded(child: _demoButton('organizer', Icons.qr_code_scanner, 'Demo organizer', 'Scan at the door')),
                          ],
                        ),
                      ),
                      if (busy) ...[
                        const SizedBox(height: 18),
                        const Text(
                          'The free server may take up to a minute to wake up the first time.',
                          textAlign: TextAlign.center,
                          style: TextStyle(color: Brand.ink300, fontSize: 12.5),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Future<void> _signIn() async {
    if (_email.text.trim().isEmpty || _password.text.isEmpty) {
      setState(() => _error = 'Enter your email and password.');
      return;
    }
    await _run('login', () => Api.instance.login(_email.text.trim(), _password.text));
  }

  Widget _demoButton(String role, IconData icon, String title, String hint) {
    final loading = _busy == role;
    return Material(
      color: Colors.white.withValues(alpha: 0.05),
      borderRadius: BorderRadius.circular(16),
      child: InkWell(
        borderRadius: BorderRadius.circular(16),
        onTap: _busy != null ? null : () => _run(role, () => Api.instance.demo(role)),
        child: Container(
          padding: const EdgeInsets.all(14),
          decoration: BoxDecoration(borderRadius: BorderRadius.circular(16), border: Border.all(color: Colors.white.withValues(alpha: 0.1))),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 38,
                height: 38,
                decoration: BoxDecoration(
                  gradient: LinearGradient(colors: [Brand.violet.withValues(alpha: 0.35), Brand.flame.withValues(alpha: 0.25)]),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: loading
                    ? const Padding(padding: EdgeInsets.all(10), child: CircularProgressIndicator(strokeWidth: 2.2))
                    : Icon(icon, size: 20, color: Brand.violetLight),
              ),
              const SizedBox(height: 12),
              Text(title, style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5)),
              const SizedBox(height: 2),
              Text(hint, style: const TextStyle(color: Brand.ink300, fontSize: 12)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _logo() {
    return Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: [Brand.violet, Brand.flame], begin: Alignment.topLeft, end: Alignment.bottomRight),
            borderRadius: BorderRadius.circular(14),
            boxShadow: [BoxShadow(color: Brand.violet.withValues(alpha: 0.4), blurRadius: 16, offset: const Offset(0, 6))],
          ),
          child: const Icon(Icons.confirmation_number_rounded, color: Colors.white),
        ),
        const SizedBox(width: 12),
        const Text.rich(
          TextSpan(children: [
            TextSpan(text: 'Event', style: TextStyle(color: Colors.white)),
            TextSpan(text: 'Pass', style: TextStyle(color: Brand.flame)),
          ]),
          style: TextStyle(fontSize: 24, fontWeight: FontWeight.w800, letterSpacing: -0.5),
        ),
      ],
    );
  }

  Widget _glow(Color color, double size) {
    return IgnorePointer(
      child: Container(
        width: size,
        height: size,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          gradient: RadialGradient(colors: [color.withValues(alpha: 0.45), color.withValues(alpha: 0)]),
        ),
      ),
    );
  }
}
