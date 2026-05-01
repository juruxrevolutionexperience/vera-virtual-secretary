<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = ['email', 'token', 'used_at', 'expires_at'];

    protected $casts = [
        'used_at'    => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function generate(string $email, int $daysValid = 30): self
    {
        return self::create([
            'email'      => strtolower(trim($email)),
            'token'      => Str::random(48),
            'expires_at' => now()->addDays($daysValid),
        ]);
    }

    public function isValid(): bool
    {
        if ($this->used_at !== null) return false;
        if ($this->expires_at !== null && $this->expires_at->isPast()) return false;
        return true;
    }

    public function markUsed(): void
    {
        $this->update(['used_at' => now()]);
    }

    public static function findValid(string $token): ?self
    {
        $invitation = self::where('token', $token)->first();
        return ($invitation && $invitation->isValid()) ? $invitation : null;
    }
}
