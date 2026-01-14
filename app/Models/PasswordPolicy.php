<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PasswordPolicy extends Model
{
    protected $fillable = [
        'name',
        'is_active',
        'min_length',
        'require_uppercase',
        'require_lowercase',
        'require_numbers',
        'require_special_chars',
        'allowed_special_chars',
        'expires',
        'expiry_days',
        'expiry_warning_days',
        'prevent_reuse',
        'history_count',
        'lockout_enabled',
        'max_attempts',
        'lockout_duration_minutes',
        'force_change_first_login',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'require_uppercase' => 'boolean',
        'require_lowercase' => 'boolean',
        'require_numbers' => 'boolean',
        'require_special_chars' => 'boolean',
        'expires' => 'boolean',
        'prevent_reuse' => 'boolean',
        'lockout_enabled' => 'boolean',
        'force_change_first_login' => 'boolean',
    ];

    // ==========================================
    // MÉTODOS ESTÁTICOS
    // ==========================================

    /**
     * Obtener la política activa
     */
    public static function getActive(): ?self
    {
        return static::where('is_active', true)->first();
    }

    /**
     * Validar contraseña contra la política
     */
    public function validate(string $password): array
    {
        $errors = [];

        if (strlen($password) < $this->min_length) {
            $errors[] = "La contraseña debe tener al menos {$this->min_length} caracteres.";
        }

        if ($this->require_uppercase && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra mayúscula.";
        }

        if ($this->require_lowercase && !preg_match('/[a-z]/', $password)) {
            $errors[] = "La contraseña debe contener al menos una letra minúscula.";
        }

        if ($this->require_numbers && !preg_match('/[0-9]/', $password)) {
            $errors[] = "La contraseña debe contener al menos un número.";
        }

        if ($this->require_special_chars) {
            $specialChars = preg_quote($this->allowed_special_chars, '/');
            if (!preg_match("/[$specialChars]/", $password)) {
                $errors[] = "La contraseña debe contener al menos un carácter especial ({$this->allowed_special_chars}).";
            }
        }

        return $errors;
    }

    /**
     * Obtener reglas de validación de Laravel
     */
    public function getLaravelRules(): array
    {
        $rules = ["min:{$this->min_length}"];

        if ($this->require_uppercase) {
            $rules[] = 'regex:/[A-Z]/';
        }

        if ($this->require_lowercase) {
            $rules[] = 'regex:/[a-z]/';
        }

        if ($this->require_numbers) {
            $rules[] = 'regex:/[0-9]/';
        }

        if ($this->require_special_chars) {
            $specialChars = preg_quote($this->allowed_special_chars, '/');
            $rules[] = "regex:/[$specialChars]/";
        }

        return $rules;
    }

    /**
     * Descripción legible de los requisitos
     */
    public function getRequirementsDescription(): string
    {
        $requirements = [];

        $requirements[] = "Mínimo {$this->min_length} caracteres";

        if ($this->require_uppercase) {
            $requirements[] = "una letra mayúscula";
        }

        if ($this->require_lowercase) {
            $requirements[] = "una letra minúscula";
        }

        if ($this->require_numbers) {
            $requirements[] = "un número";
        }

        if ($this->require_special_chars) {
            $requirements[] = "un carácter especial";
        }

        return "La contraseña debe contener: " . implode(', ', $requirements) . ".";
    }
}
