<?php

namespace App\Http\Controllers\Organizer;

use App\Enums\EventStatus;
use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function create()
    {
        return view('organizer.events.form', [
            'event' => new Event(['theme' => random_int(0, 5)]),
            'tickets' => [['id' => null, 'name' => 'General admission', 'description' => '', 'price' => '25', 'quantity' => 100, 'max_per_order' => 6]],
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $event = DB::transaction(function () use ($request, $data) {
            $event = Event::create([
                ...$data['event'],
                'organizer_id' => $request->user()->id,
                'slug' => Event::uniqueSlug($data['event']['title']),
                'status' => EventStatus::Draft,
            ]);

            $this->syncTickets($event, $data['tickets']);

            return $event;
        });

        return redirect()->route('organizer.events.edit', $event)
            ->with('status', 'Event created as a draft. Publish it when you are ready.');
    }

    public function edit(Event $event)
    {
        Gate::authorize('manage', $event);

        $event->load(['ticketTypes' => fn ($q) => $q->withReserved()]);

        return view('organizer.events.form', [
            'event' => $event,
            'tickets' => $event->ticketTypes->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'price' => number_format($t->price_cents / 100, 2, '.', ''),
                'quantity' => $t->quantity,
                'max_per_order' => $t->max_per_order,
                'sold' => $t->quantity - $t->available(),
            ])->all(),
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        $data = $this->validated($request);

        $notice = DB::transaction(function () use ($event, $data) {
            $event->update([
                ...$data['event'],
                'slug' => Event::uniqueSlug($data['event']['title'], $event->id),
            ]);

            return $this->syncTickets($event, $data['tickets']);
        });

        return redirect()->route('organizer.events.edit', $event)
            ->with('status', $notice ?? 'Event saved.');
    }

    public function publish(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        if ($event->ticketTypes()->doesntExist()) {
            return back()->with('error', 'Add at least one ticket type before publishing.');
        }

        $event->update(['status' => $event->isPublished() ? EventStatus::Draft : EventStatus::Published]);

        return back()->with('status', $event->isPublished() ? 'Event is live. Tickets are on sale.' : 'Event moved back to draft.');
    }

    public function destroy(Event $event): RedirectResponse
    {
        Gate::authorize('manage', $event);

        if ($event->orders()->where('status', 'paid')->exists()) {
            return back()->with('error', 'This event has paid orders, so it cannot be deleted. Move it back to draft instead.');
        }

        $event->delete();

        return redirect()->route('organizer.dashboard')->with('status', 'Event deleted.');
    }

    /** @return array{event: array<string, mixed>, tickets: array<int, array<string, mixed>>} */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(Event::CATEGORIES)],
            'description' => ['required', 'string', 'max:5000'],
            'venue' => ['required', 'string', 'max:120'],
            'city' => ['required', 'string', 'max:80'],
            'starts_at' => ['required', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'theme' => ['required', 'integer', 'between:0,5'],
            'tickets' => ['required', 'array', 'min:1', 'max:8'],
            'tickets.*.id' => ['nullable', 'integer'],
            'tickets.*.name' => ['required', 'string', 'max:60'],
            'tickets.*.description' => ['nullable', 'string', 'max:140'],
            'tickets.*.price' => ['required', 'numeric', 'min:0', 'max:100000'],
            'tickets.*.quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'tickets.*.max_per_order' => ['required', 'integer', 'min:1', 'max:10'],
        ]);

        return [
            'event' => collect($validated)->only(['title', 'category', 'description', 'venue', 'city', 'starts_at', 'ends_at', 'theme'])->all(),
            'tickets' => $validated['tickets'],
        ];
    }

    /**
     * Create/update ticket types. Types that already have sales are never deleted and their
     * quantity can't drop below what has been sold or is currently held.
     *
     * @param  array<int, array<string, mixed>>  $tickets
     */
    private function syncTickets(Event $event, array $tickets): ?string
    {
        $existing = $event->ticketTypes()->withReserved()->get()->keyBy('id');
        $keep = [];
        $warnings = [];

        foreach (array_values($tickets) as $sort => $row) {
            $payload = [
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'price_cents' => (int) round(((float) $row['price']) * 100),
                'quantity' => (int) $row['quantity'],
                'max_per_order' => (int) $row['max_per_order'],
                'sort' => $sort,
            ];

            $type = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            if ($type) {
                $taken = $type->quantity - $type->available();

                if ($payload['quantity'] < $taken) {
                    $payload['quantity'] = $taken;
                    $warnings[] = "{$type->name} quantity was kept at {$taken} because that many are already sold or held.";
                }

                $type->update($payload);
                $keep[] = $type->id;
            } else {
                $keep[] = $event->ticketTypes()->create($payload)->id;
            }
        }

        foreach ($existing->except($keep) as $removed) {
            if ($removed->orderItems()->exists()) {
                $warnings[] = "{$removed->name} has sales so it was kept.";
            } else {
                $removed->delete();
            }
        }

        return $warnings === [] ? null : 'Saved. '.implode(' ', $warnings);
    }
}
