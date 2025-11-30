<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'color',
        'icon',
        'order',
        'is_system',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relación con el usuario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con emails
     */
    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }

    /**
     * Relación con reglas de email
     */
    public function rules(): HasMany
    {
        return $this->hasMany(EmailRule::class);
    }

    /**
     * Scope para categorías activas
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para categorías del sistema
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope para categorías personalizadas
     */
    public function scopeCustom($query)
    {
        return $query->where('is_system', false);
    }

    /**
     * Obtener el conteo de emails en esta categoría
     */
    public function getEmailCountAttribute(): int
    {
        return $this->emails()->count();
    }

    /**
     * Obtener emails no leídos
     */
    public function getUnreadCountAttribute(): int
    {
        return $this->emails()->where('is_read', false)->count();
    }

    /**
     * Ordenar por campo 'order'
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
