<?php

namespace App\Policies;

use App\Models\Contact;
use App\Models\User;

class ContactPolicy
{
    /**
     * Determinar si el usuario puede ver el contacto
     */
    public function view(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar el contacto
     */
    public function update(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar el contacto
     */
    public function delete(User $user, Contact $contact): bool
    {
        return $contact->user_id === $user->id;
    }
}
