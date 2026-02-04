<?php

namespace App\Policies;

use App\Models\EmailAlert;
use App\Models\User;

class EmailAlertPolicy
{
    /**
     * Determinar si el usuario puede ver la alerta
     */
    public function view(User $user, EmailAlert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar la alerta
     */
    public function update(User $user, EmailAlert $alert): bool
    {
        return $alert->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar la alerta
     */
    public function delete(User $user, EmailAlert $alert): bool
    {
        return $alert->user_id === $user->id;
    }
}
