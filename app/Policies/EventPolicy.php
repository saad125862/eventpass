<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    /** Edit, publish, view attendees and run check-in: the event's organizer or an admin. */
    public function manage(User $user, Event $event): bool
    {
        return $user->isAdmin() || ($user->canOrganize() && $event->organizer_id === $user->id);
    }
}
