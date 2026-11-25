import 'package:eventpass_mobile/models.dart';
import 'package:eventpass_mobile/theme.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  group('formatting', () {
    test('money formats cents and shows Free for zero', () {
      expect(fmtMoney(2500), '\$25.00');
      expect(fmtMoney(999), '\$9.99');
      expect(fmtMoney(0), 'Free');
    });

    test('date formats as weekday, month day and 12-hour time', () {
      final d = DateTime(2026, 12, 6, 9, 5); // a Sunday
      expect(fmtDateTime(d), 'Sun, Dec 6 · 9:05 AM');
      expect(fmtDateTime(DateTime(2026, 12, 6, 0, 0)), 'Sun, Dec 6 · 12:00 AM');
      expect(fmtDateTime(DateTime(2026, 12, 6, 15, 30)), 'Sun, Dec 6 · 3:30 PM');
    });
  });

  group('API models', () {
    test('user parses and builds initials', () {
      final u = AppUser.fromJson({'id': 1, 'name': 'Olivia Organizer', 'email': 'o@x.test', 'role': 'organizer', 'can_organize': true});
      expect(u.initials, 'OO');
      expect(u.canOrganize, isTrue);
      expect(AppUser.fromJson(u.toJson()).email, 'o@x.test');
    });

    test('event flags sold out and almost gone', () {
      EventItem event(int remaining, int capacity) => EventItem.fromJson({
            'slug': 's',
            'title': 'T',
            'category': 'Tech',
            'venue': 'V',
            'city': 'C',
            'starts_at': '2026-12-06T09:00:00+00:00',
            'theme': 2,
            'remaining': remaining,
            'capacity': capacity,
          });

      expect(event(0, 100).soldOut, isTrue);
      expect(event(10, 100).almostGone, isTrue);
      expect(event(60, 100).almostGone, isFalse);
      expect(event(60, 100).soldOut, isFalse);
    });

    test('scan results map to colours via ok / warn', () {
      ScanResult result(String r) => ScanResult.fromJson({
            'result': r,
            'message': 'm',
            'detail': 'd',
            'counts': {'checked_in': 1, 'total': 5},
          });

      expect(result('ok').ok, isTrue);
      expect(result('used').warn, isTrue);
      expect(result('invalid').ok, isFalse);
      expect(result('ok').counts.total, 5);
    });
  });
}
