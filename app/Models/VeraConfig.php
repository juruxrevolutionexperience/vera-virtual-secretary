<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VeraConfig extends Model
{
    protected $fillable = [
        'user_id',

        // Identidad del negocio (Cerebro Capa 1)
        'business_name',
        'owner_name',
        'tone',
        'language',

        // Horario y reuniones
        'working_hours',
        'meeting_duration',
        'buffer_minutes',

        // Comportamiento de Vera
        'unknown_response',
        'escalation_triggers',
        'mode',
        'vera_signature',

        // Personalidad rica (Fase A)
        'personality_traits',
        'years_at_business',
        'pov_about_business',
        'refers_to_owner_as',

        // Google Calendar (Sprint 3)
        'google_calendar_id',
        'google_access_token',
        'google_refresh_token',
        'google_token_expires_at',

        // Canales de mensajería (Paso D)
        'telegram_bot_token',
        'telegram_chat_id',
        'whatsapp_phone_number_id',
        'whatsapp_access_token',

        // Contexto RAG del negocio
        'writing_style',
        'important_contacts',
        'confidence_threshold',

        // Onboarding
        'onboarding_completed',
        'onboarding_completed_at',

        // Web de reservas
        'auto_confirm_bookings',
    ];

    protected $casts = [
        'working_hours'       => 'array',
        'escalation_triggers' => 'array',
        'writing_style'       => 'array',
        'important_contacts'  => 'array',
        'personality_traits'  => 'array',
        'years_at_business'   => 'integer',
        'confidence_threshold'    => 'decimal:2',
        'onboarding_completed'    => 'boolean',
        'onboarding_completed_at' => 'datetime',
        'google_token_expires_at' => 'datetime',
        'meeting_duration'        => 'integer',
        'buffer_minutes'          => 'integer',
        'auto_confirm_bookings'   => 'boolean',
        // Tokens sensibles — cifrados en reposo con APP_KEY
        'google_access_token'  => 'encrypted',
        'google_refresh_token' => 'encrypted',
        'telegram_bot_token'   => 'encrypted',
        'whatsapp_access_token'=> 'encrypted',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * ¿Está el Cerebro de Vera configurado con los campos obligatorios?
     * Vera no arranca sin working_hours ni tone.
     */
    public function isBrainReady(): bool
    {
        return !empty($this->working_hours) && !empty($this->tone);
    }

    /**
     * ¿Tiene Google vinculado? (Calendar + Gmail comparten el mismo token OAuth)
     */
    public function hasCalendarConnected(): bool
    {
        return !empty($this->google_calendar_id) && !empty($this->google_access_token);
    }

    /**
     * ¿Tiene Gmail conectado?
     * Mismo token que Calendar — si conectó Google, tiene acceso a ambos.
     */
    public function hasGmailConnected(): bool
    {
        return $this->hasCalendarConnected();
    }

    /**
     * Horario laboral de un día concreto.
     * Devuelve ['start' => '09:00', 'end' => '18:00'] o null si cerrado.
     *
     * @param string $day  lun|mar|mie|jue|vie|sab|dom
     */
    public function getWorkingHoursForDay(string $day): ?array
    {
        $hours = $this->working_hours;
        if (empty($hours) || empty($hours[$day])) {
            return null;
        }

        $range = explode('-', $hours[$day]);
        if (count($range) !== 2) {
            return null;
        }

        return ['start' => $range[0], 'end' => $range[1]];
    }

    /**
     * ¿Vera está dentro del horario laboral ahora mismo?
     */
    public function isWithinWorkingHours(): bool
    {
        $dayMap = [0 => 'dom', 1 => 'lun', 2 => 'mar', 3 => 'mie', 4 => 'jue', 5 => 'vie', 6 => 'sab'];
        $today  = $dayMap[now()->dayOfWeek] ?? null;

        if (!$today) {
            return false;
        }

        $hours = $this->getWorkingHoursForDay($today);
        if (!$hours) {
            return false; // Día no laboral
        }

        $now   = now()->format('H:i');
        $start = $hours['start'];
        $end   = $hours['end'];

        if ($start <= $end) {
            return $now >= $start && $now <= $end;
        }

        // Cruce de medianoche
        return $now >= $start || $now <= $end;
    }

    /**
     * ¿Tiene Telegram conectado?
     */
    public function hasTelegramConnected(): bool
    {
        return !empty($this->telegram_bot_token);
    }

    /**
     * ¿Tiene WhatsApp conectado?
     */
    public function hasWhatsAppConnected(): bool
    {
        return !empty($this->whatsapp_phone_number_id) && !empty($this->whatsapp_access_token);
    }

    /**
     * Canales activos del usuario.
     */
    public function getActiveChannels(): array
    {
        $channels = [];
        if ($this->hasTelegramConnected())  $channels[] = 'telegram';
        if ($this->hasWhatsAppConnected())  $channels[] = 'whatsapp';
        if ($this->hasCalendarConnected())  $channels[] = 'google_calendar';
        if ($this->hasGmailConnected())     $channels[] = 'gmail';
        return $channels;
    }

    /**
     * ¿El mensaje contiene algún trigger de escalado?
     */
    public function shouldEscalate(string $message): bool
    {
        $triggers = $this->escalation_triggers ?? [];
        if (empty($triggers)) {
            return false;
        }

        $messageLower = strtolower($message);
        foreach ($triggers as $trigger) {
            if (str_contains($messageLower, strtolower($trigger))) {
                return true;
            }
        }

        return false;
    }
}
