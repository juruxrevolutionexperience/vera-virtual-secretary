<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, Billable;

    protected $fillable = [
        'name',
        'email',
        'password',
        // === campos de billing añadidos a la tabla users ===
        'is_paid',
        'stripe_customer_id',
        'stripe_subscription_id',
        'current_period_end',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_paid' => 'boolean',
            'current_period_end' => 'datetime',
        ];
    }

    // Acceso activo al app (flag + periodo vigente)
    public function getHasActiveAccessAttribute(): bool
    {
        return $this->is_paid && (
            is_null($this->current_period_end) || $this->current_period_end->isFuture()
        );
    }
}
