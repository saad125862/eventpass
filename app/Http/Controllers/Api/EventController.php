<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('category');

        $events = Event::query()
            ->published()
            ->upcoming()
            ->with(['ticketTypes' => fn ($q) => $q->withReserved()])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $q->where(fn ($w) => $w
                    ->whereRaw('lower(title) like ?', [$like])
                    ->orWhereRaw('lower(city) like ?', [$like]));
            })
            ->when(in_array($category, Event::CATEGORIES, true), fn ($q) => $q->where('category', $category))
            ->orderBy('starts_at')
            ->limit(50)
            ->get();

        return response()->json(['data' => $events->map(fn (Event $e) => $this->present($e))->values()]);
    }

    public function show(Event $event): JsonResponse
    {
        abort_unless($event->isPublished(), 404);

        $event->load(['organizer', 'ticketTypes' => fn ($q) => $q->withReserved()]);

        return response()->json(['data' => $this->present($event, withDetail: true)]);
    }

    /** @return array<string, mixed> */
    private function present(Event $event, bool $withDetail = false): array
    {
        $data = [
            'id' => $event->id,
            'slug' => $event->slug,
            'title' => $event->title,
            'category' => $event->category,
            'venue' => $event->venue,
            'city' => $event->city,
            'starts_at' => $event->starts_at->toIso8601String(),
            'theme' => $event->theme,
            'min_price_cents' => $event->minPriceCents(),
            'remaining' => $event->remaining(),
            'capacity' => $event->capacity(),
        ];

        if ($withDetail) {
            $data['description'] = $event->description;
            $data['organizer'] = $event->organizer->name;
            $data['ticket_types'] = $event->ticketTypes->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'description' => $t->description,
                'price_cents' => $t->price_cents,
                'available' => $t->available(),
            ])->values();
        }

        return $data;
    }
}
