import 'package:flutter/material.dart';
import 'package:flutter/services.dart';

import 'api.dart';
import 'models.dart';
import 'screens/home_shell.dart';
import 'screens/login_screen.dart';
import 'theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  SystemChrome.setSystemUIOverlayStyle(const SystemUiOverlayStyle(
    statusBarColor: Colors.transparent,
    statusBarIconBrightness: Brightness.light,
    systemNavigationBarColor: Brand.ink900,
    systemNavigationBarIconBrightness: Brightness.light,
  ));
  await Api.instance.load();
  runApp(const EventPassApp());
}

class EventPassApp extends StatelessWidget {
  const EventPassApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'EventPass',
      debugShowCheckedModeBanner: false,
      theme: buildTheme(),
      home: ValueListenableBuilder<AppUser?>(
        valueListenable: Api.instance.session,
        builder: (context, user, _) => AnimatedSwitcher(
          duration: const Duration(milliseconds: 350),
          child: user == null ? const LoginScreen(key: ValueKey('login')) : HomeShell(key: ValueKey('home-${user.id}'), user: user),
        ),
      ),
    );
  }
}
