<?php

namespace App\Policies;

use App\Models\EscalationRule;
use App\Models\User;

class EscalationRulePolicy
{
    /**
     * Determinar si el usuario puede ver la regla
     */
    public function view(User $user, EscalationRule $rule): bool
    {
        return $rule->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede actualizar la regla
     */
    public function update(User $user, EscalationRule $rule): bool
    {
        return $rule->user_id === $user->id;
    }

    /**
     * Determinar si el usuario puede eliminar la regla
     */
    public function delete(User $user, EscalationRule $rule): bool
    {
        return $rule->user_id === $user->id;
    }
}
