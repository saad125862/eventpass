class AppUser {
  AppUser({required this.id, required this.name, required this.email, required this.role, required this.canOrganize});

  final int id;
  final String name;
  final String email;
  final String role;
  final bool canOrganize;

  factory AppUser.fromJson(Map<String, dynamic> j) => AppUser(
        id: j['id'] as int,
        name: j['name'] as String,
        email: j['email'] as String,
        role: j['role'] as String,
        canOrganize: j['can_organize'] as bool,
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'email': email, 'role': role, 'can_organize': canOrganize};

  String get initials {
    final parts = name.trim().split(RegExp(r'\s+'));
    final a = parts.isNotEmpty && parts[0].isNotEmpty ? parts[0][0] : '?';
    final b = parts.length > 1 && parts[1].isNotEmpty ? parts[1][0] : '';
    return (a + b).toUpperCase();
  }
}

class TicketType {
  TicketType({required this.id, required this.name, this.description, required this.priceCents, required this.available});

  final int id;
  final String name;
  final String? description;
  final int priceCents;
  final int available;

  factory TicketType.fromJson(Map<String, dynamic> j) => TicketType(
        id: j['id'] as int,
        name: j['name'] as String,
        description: j['description'] as String?,
        priceCents: j['price_cents'] as int,
        available: j['available'] as int,
      );
}

class EventItem {
  EventItem({
    required this.slug,
    required this.title,
    required this.category,
    required this.venue,
    required this.city,
    required this.startsAt,
    required this.theme,
    this.minPriceCents,
    this.remaining = 0,
    this.capacity = 0,
    this.description,
    this.organizer,
    this.ticketTypes = const [],
  });

  final String slug;
  final String title;
  final String category;
  final String venue;
  final String city;
  final DateTime startsAt;
  final int theme;
  final int? minPriceCents;
  final int remaining;
  final int capacity;
  final String? description;
  final String? organizer;
  final List<TicketType> ticketTypes;

  bool get soldOut => capacity > 0 && remaining == 0;
  bool get almostGone => !soldOut && capacity > 0 && remaining / capacity <= 0.2;

  factory EventItem.fromJson(Map<String, dynamic> j) => EventItem(
        slug: j['slug'] as String,
        title: j['title'] as String,
        category: (j['category'] ?? '') as String,
        venue: j['venue'] as String,
        city: (j['city'] ?? '') as String,
        startsAt: DateTime.parse(j['starts_at'] as String),
        theme: (j['theme'] ?? 0) as int,
        minPriceCents: j['min_price_cents'] as int?,
        remaining: (j['remaining'] ?? 0) as int,
        capacity: (j['capacity'] ?? 0) as int,
        description: j['description'] as String?,
        organizer: j['organizer'] as String?,
        ticketTypes: ((j['ticket_types'] ?? []) as List).map((t) => TicketType.fromJson(t as Map<String, dynamic>)).toList(),
      );
}

class Ticket {
  Ticket({
    required this.code,
    required this.shortCode,
    required this.status,
    required this.type,
    required this.qr,
    required this.event,
    this.checkedInAt,
  });

  final String code;
  final String shortCode;
  final String status;
  final String type;
  final String qr;
  final EventItem event;
  final DateTime? checkedInAt;

  bool get valid => status == 'valid';

  factory Ticket.fromJson(Map<String, dynamic> j) => Ticket(
        code: j['code'] as String,
        shortCode: j['short_code'] as String,
        status: j['status'] as String,
        type: j['type'] as String,
        qr: j['qr'] as String,
        checkedInAt: j['checked_in_at'] == null ? null : DateTime.parse(j['checked_in_at'] as String),
        event: EventItem.fromJson(j['event'] as Map<String, dynamic>),
      );
}

class CheckInCounts {
  CheckInCounts({required this.checkedIn, required this.total});

  final int checkedIn;
  final int total;

  factory CheckInCounts.fromJson(Map<String, dynamic> j) =>
      CheckInCounts(checkedIn: (j['checked_in'] ?? 0) as int, total: (j['total'] ?? 0) as int);
}

class OrganizerEvent {
  OrganizerEvent({required this.slug, required this.title, required this.venue, required this.startsAt, required this.theme, required this.counts});

  final String slug;
  final String title;
  final String venue;
  final DateTime startsAt;
  final int theme;
  final CheckInCounts counts;

  factory OrganizerEvent.fromJson(Map<String, dynamic> j) => OrganizerEvent(
        slug: j['slug'] as String,
        title: j['title'] as String,
        venue: (j['venue'] ?? '') as String,
        startsAt: DateTime.parse(j['starts_at'] as String),
        theme: (j['theme'] ?? 0) as int,
        counts: CheckInCounts.fromJson(j['counts'] as Map<String, dynamic>),
      );
}

/// Result of scanning a ticket: ok | used | invalid | wrong_event | void
class ScanResult {
  ScanResult({required this.result, required this.message, required this.detail, required this.counts});

  final String result;
  final String message;
  final String detail;
  final CheckInCounts counts;

  bool get ok => result == 'ok';
  bool get warn => result == 'used';

  factory ScanResult.fromJson(Map<String, dynamic> j) => ScanResult(
        result: j['result'] as String,
        message: j['message'] as String,
        detail: (j['detail'] ?? '') as String,
        counts: CheckInCounts.fromJson(j['counts'] as Map<String, dynamic>),
      );
}
