<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CheckInController extends Controller
{
    public function __construct(private readonly CheckInService $checkIn) {}

    /** Events the signed-in organizer (or any event, for admins) can run check-in for. */
    public function events(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $events = Event::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->where('organizer_id', $user->id))
            ->orderBy('starts_at')
            ->get();

        return response()->json(['data' => $events->map(fn (Event $e) => [
            'slug' => $e->slug,
            'title' => $e->title,
            'venue' => $e->venue,
            'starts_at' => $e->starts_at->toIso8601String(),
            'theme' => $e->theme,
            'counts' => $this->checkIn->counts($e),
        ])->values()]);
    }

    /** Same atomic one-scan logic the website scanner uses. */
    public function verify(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('manage', $event);

        $data = $request->validate(['code' => ['required', 'string', 'max:200']]);

        return response()->json($this->checkIn->check($event, $data['code'], $request->user()));
    }
}
