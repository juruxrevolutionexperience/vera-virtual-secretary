<?php

namespace App\Policies;

use App\Models\FollowUpReminder;
use App\Models\User;

class FollowUpReminderPolicy
{
    /**
     * Determinar si el usuario puede ver el recordatorio
     */
    public function view(User $user, FollowUpReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar el recordatorio
     */
    public function update(User $user, FollowUpReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar el recordatorio
     */
    public function delete(User $user, FollowUpReminder $reminder): bool
    {
        return $reminder->user_id === $user->id;
    }
}
