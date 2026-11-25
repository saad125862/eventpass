<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EventStatus;
use App\Enums\OrderStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('admin.dashboard', [
            'stats' => [
                'users' => User::count(),
                'events' => Event::count(),
                'tickets' => Ticket::count(),
                'revenue' => (int) Order::query()->where('status', OrderStatus::Paid->value)->sum('total_cents'),
            ],
            'users' => User::query()->latest()->limit(25)->get(),
            'events' => Event::query()->with('organizer')->latest()->limit(25)->get(),
            'roles' => Role::cases(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', Rule::enum(Role::class)]]);

        if ($user->is($request->user())) {
            return back()->with('error', 'You cannot change your own role.');
        }

        $user->update(['role' => $data['role']]);

        return back()->with('status', "{$user->name} is now {$user->role->label()}.");
    }

    public function toggleEvent(Event $event): RedirectResponse
    {
        $event->update(['status' => $event->isPublished() ? EventStatus::Draft : EventStatus::Published]);

        return back()->with('status', $event->isPublished() ? 'Event published.' : 'Event unpublished.');
    }
}
