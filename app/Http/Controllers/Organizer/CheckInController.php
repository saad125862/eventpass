<?php

namespace App\Http\Controllers\Organizer;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Services\CheckInService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CheckInController extends Controller
{
    public function __construct(private readonly CheckInService $checkIn) {}

    public function scan(Event $event)
    {
        Gate::authorize('manage', $event);

        return view('organizer.scan', [
            'event' => $event,
            'counts' => $this->checkIn->counts($event),
        ]);
    }

    public function verify(Request $request, Event $event): JsonResponse
    {
        Gate::authorize('manage', $event);

        $data = $request->validate(['code' => ['required', 'string', 'max:200']]);

        return response()->json($this->checkIn->check($event, $data['code'], $request->user()));
    }
}
