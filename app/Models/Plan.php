<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'email_limit',
        'token_limit',
        'price_eur',
        'features',
        'is_active',
    ];

    protected $casts = [
        'features'    => 'array',
        'is_active'   => 'boolean',
        'email_limit' => 'integer',
        'token_limit' => 'integer',
        'price_eur'   => 'decimal:2',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'plan_type', 'slug');
    }

    public function isUnlimited(): bool
    {
        return $this->email_limit === null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }
}
