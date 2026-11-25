<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('category');
        $category = in_array($category, Event::CATEGORIES, true) ? $category : null;

        $events = Event::query()
            ->published()
            ->upcoming()
            ->with(['ticketTypes' => fn ($q) => $q->withReserved()])
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.mb_strtolower($search).'%';
                $q->where(fn ($w) => $w
                    ->whereRaw('lower(title) like ?', [$like])
                    ->orWhereRaw('lower(city) like ?', [$like])
                    ->orWhereRaw('lower(venue) like ?', [$like]));
            })
            ->when($category, fn ($q) => $q->where('category', $category))
            ->orderBy('starts_at')
            ->paginate(9)
            ->withQueryString();

        return view('events.index', [
            'events' => $events,
            'search' => $search,
            'category' => $category,
            'categories' => Event::CATEGORIES,
        ]);
    }

    public function show(Request $request, Event $event)
    {
        // Drafts are only visible to the organizer / admins (as a preview).
        if (! $event->isPublished()) {
            abort_unless($request->user() && Gate::allows('manage', $event), 404);
        }

        // Remember where a guest was heading so login brings them back to book.
        if (! $request->user()) {
            $request->session()->put('url.intended', $request->url());
        }

        $event->load(['organizer', 'ticketTypes' => fn ($q) => $q->withReserved()]);

        $pickerTypes = $event->ticketTypes->map(fn ($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'description' => $t->description,
            'price_cents' => $t->price_cents,
            'available' => $t->available(),
            'max' => $t->max_per_order,
            'on_sale' => $t->isOnSale(),
            'sold_percent' => $t->soldPercent(),
        ])->values();

        return view('events.show', [
            'event' => $event,
            'pickerTypes' => $pickerTypes,
            'idempotencyKey' => (string) Str::uuid(),
            'isPreview' => ! $event->isPublished(),
        ]);
    }
}
