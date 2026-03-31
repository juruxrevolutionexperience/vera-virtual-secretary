<?php

namespace App\Services;

use App\Models\User;
use App\Models\VeraConfig;
use App\Models\Meeting;
use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Illuminate\Support\Facades\Log;

/**
 * GoogleCalendarService — Sprint 3
 *
 * Core del producto: Vera consulta disponibilidad real y crea/modifica/cancela
 * eventos en Google Calendar del usuario.
 *
 * Métodos principales:
 * - getAvailableSlots()   → propone slots libres al cliente
 * - createEvent()         → confirma la reunión creando el evento
 * - updateEvent()         → modifica fecha/hora de un evento existente
 * - cancelEvent()         → cancela un evento en Calendar
 * - checkConflicts()      → verifica si un slot concreto está libre
 */
class GoogleCalendarService
{
    private GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
    }

    // ─── Gestión de tokens ────────────────────────────────────────────────────

    /**
     * Configura el cliente con los tokens OAuth del usuario.
     * Refresca el token automáticamente si ha expirado.
     */
    private function authenticateUser(VeraConfig $config): void
    {
        $this->client->setAccessToken([
            'access_token'  => $config->google_access_token,
            'refresh_token' => $config->google_refresh_token,
            'expires_in'    => $config->google_token_expires_at
                ? now()->diffInSeconds($config->google_token_expires_at, false)
                : 0,
        ]);

        // Refrescar si expiró
        if ($this->client->isAccessTokenExpired()) {
            if (!$config->google_refresh_token) {
                throw new \RuntimeException('Google Calendar no tiene refresh_token. El usuario debe reconectar su cuenta.');
            }

            $newToken = $this->client->fetchAccessTokenWithRefreshToken($config->google_refresh_token);

            $config->update([
                'google_access_token'     => $newToken['access_token'],
                'google_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
            ]);

            Log::info('🔄 Google token refrescado', ['user_id' => $config->user_id]);
        }
    }

    /**
     * Devuelve una instancia del servicio Calendar autenticada para el usuario.
     */
    private function getCalendarService(VeraConfig $config): Calendar
    {
        $this->authenticateUser($config);
        return new Calendar($this->client);
    }

    // ─── Disponibilidad ───────────────────────────────────────────────────────

    /**
     * Obtiene slots disponibles para los próximos N días.
     *
     * @param User   $user
     * @param int    $daysAhead     Cuántos días hacia adelante buscar
     * @param int    $maxSlots      Máximo de slots a devolver
     * @param string $preferredDate Fecha preferida por el cliente (Y-m-d), opcional
     *
     * @return array  Array de ['start' => Carbon, 'end' => Carbon, 'label' => string]
     */
    public function getAvailableSlots(
        User $user,
        int $daysAhead = 7,
        int $maxSlots = 6,
        ?string $preferredDate = null
    ): array {
        $config = VeraConfig::where('user_id', $user->id)->firstOrFail();

        if (!$config->hasCalendarConnected()) {
            throw new \RuntimeException('Google Calendar no está conectado para este usuario.');
        }

        $service      = $this->getCalendarService($config);
        $calendarId   = 'primary';
        $duration     = $config->meeting_duration ?? 45;
        $buffer       = $config->buffer_minutes ?? 15;
        $workingHours = $config->working_hours ?? [];

        // Rango de búsqueda
        $start = $preferredDate
            ? Carbon::parse($preferredDate)->startOfDay()
            : now()->addHour()->roundMinutes(15);

        $end = $start->copy()->addDays($daysAhead)->endOfDay();

        // Obtener eventos ya existentes en Calendar
        $existingEvents = $service->events->listEvents($calendarId, [
            'timeMin'      => $start->toRfc3339String(),
            'timeMax'      => $end->toRfc3339String(),
            'singleEvents' => true,
            'orderBy'      => 'startTime',
        ]);

        // Construir lista de bloques ocupados
        $busyBlocks = [];
        foreach ($existingEvents->getItems() as $event) {
            $eventStart = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();
            $eventEnd   = $event->getEnd()->getDateTime() ?? $event->getEnd()->getDate();

            if ($eventStart && $eventEnd) {
                $busyBlocks[] = [
                    'start' => Carbon::parse($eventStart),
                    'end'   => Carbon::parse($eventEnd),
                ];
            }
        }

        // Generar slots libres dentro del horario laboral
        $slots   = [];
        $current = $start->copy();

        // English keys as stored by the frontend
        $dayMap = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];

        while ($current->lessThan($end) && count($slots) < $maxSlots) {
            $dayKey = $dayMap[$current->dayOfWeek];
            $dayConfig = $workingHours[$dayKey] ?? null;

            if (!empty($dayConfig) && ($dayConfig['enabled'] ?? false)) {
                $dayStart = $dayConfig['start'] ?? '09:00';
                $dayEnd   = $dayConfig['end']   ?? '18:00';

                $slotStart = $current->copy()->setTimeFromTimeString($dayStart . ':00');
                $dayEndTime = $current->copy()->setTimeFromTimeString($dayEnd . ':00');

                // Si ya pasamos el inicio del día, empezar desde ahora
                if ($slotStart->lessThan(now()->addMinutes(30))) {
                    $slotStart = now()->addMinutes(30)->roundMinutes(15);
                }

                while ($slotStart->copy()->addMinutes($duration)->lessThanOrEqualTo($dayEndTime)) {
                    $slotEnd = $slotStart->copy()->addMinutes($duration);

                    if ($this->isSlotFree($slotStart, $slotEnd, $busyBlocks, $buffer)) {
                        $slots[] = [
                            'start' => $slotStart->copy(),
                            'end'   => $slotEnd->copy(),
                            'label' => $slotStart->translatedFormat('l d \d\e F \a \l\a\s H:i'),
                        ];

                        if (count($slots) >= $maxSlots) {
                            break 2;
                        }
                    }

                    $slotStart->addMinutes(30); // Buscar en intervalos de 30 min
                }
            }

            $current->addDay()->startOfDay();
        }

        return $slots;
    }

    /**
     * Verifica si un slot concreto está libre (sin conflictos).
     *
     * @param User   $user
     * @param Carbon $start
     * @param Carbon $end
     */
    public function checkConflicts(User $user, Carbon $start, Carbon $end): bool
    {
        $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $service = $this->getCalendarService($config);

        $events = $service->events->listEvents('primary', [
            'timeMin'      => $start->toRfc3339String(),
            'timeMax'      => $end->toRfc3339String(),
            'singleEvents' => true,
        ]);

        return count($events->getItems()) === 0;
    }

    // ─── CRUD de eventos ──────────────────────────────────────────────────────

    /**
     * Crea un evento en Google Calendar y actualiza el modelo Meeting.
     *
     * @return string  google_event_id del evento creado
     */
    public function createEvent(User $user, Meeting $meeting): string
    {
        $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $service = $this->getCalendarService($config);

        $start = Carbon::parse($meeting->scheduled_at)->timezone($meeting->timezone);
        $end   = $start->copy()->addMinutes($meeting->duration_minutes);

        $event = new Event([
            'summary'     => "Reunión con {$meeting->contact_name}",
            'description' => $meeting->notes ?? "Reunión gestionada por Vera",
            'attendees'   => $meeting->contact_email
                ? [['email' => $meeting->contact_email]]
                : [],
        ]);

        $event->setStart(new EventDateTime([
            'dateTime' => $start->toRfc3339String(),
            'timeZone' => $meeting->timezone,
        ]));

        $event->setEnd(new EventDateTime([
            'dateTime' => $end->toRfc3339String(),
            'timeZone' => $meeting->timezone,
        ]));

        $createdEvent = $service->events->insert('primary', $event, [
            'sendUpdates' => 'all', // Envía notificación a los asistentes
        ]);

        $googleEventId = $createdEvent->getId();
        $meetLink      = $createdEvent->getHangoutLink();

        // Actualizar modelo Meeting
        $meeting->update([
            'google_event_id' => $googleEventId,
            'google_meet_link'=> $meetLink,
            'status'          => 'confirmed',
        ]);

        Log::info('✅ Evento creado en Google Calendar', [
            'user_id'        => $user->id,
            'meeting_id'     => $meeting->id,
            'google_event_id'=> $googleEventId,
        ]);

        return $googleEventId;
    }

    /**
     * Modifica fecha/hora de un evento existente.
     */
    public function updateEvent(User $user, Meeting $meeting, Carbon $newStart): void
    {
        if (!$meeting->google_event_id) {
            throw new \RuntimeException("Meeting #{$meeting->id} no tiene google_event_id.");
        }

        $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $service = $this->getCalendarService($config);

        $event = $service->events->get('primary', $meeting->google_event_id);

        $newEnd = $newStart->copy()->addMinutes($meeting->duration_minutes);

        $event->setStart(new EventDateTime([
            'dateTime' => $newStart->toRfc3339String(),
            'timeZone' => $meeting->timezone,
        ]));
        $event->setEnd(new EventDateTime([
            'dateTime' => $newEnd->toRfc3339String(),
            'timeZone' => $meeting->timezone,
        ]));

        $service->events->update('primary', $meeting->google_event_id, $event, [
            'sendUpdates' => 'all',
        ]);

        $meeting->update([
            'scheduled_at' => $newStart,
            'status'       => 'confirmed',
        ]);

        Log::info('✅ Evento actualizado en Google Calendar', [
            'user_id'        => $user->id,
            'meeting_id'     => $meeting->id,
            'google_event_id'=> $meeting->google_event_id,
            'new_start'      => $newStart->toDateTimeString(),
        ]);
    }

    /**
     * Cancela un evento en Google Calendar.
     */
    public function cancelEvent(User $user, Meeting $meeting): void
    {
        if (!$meeting->google_event_id) {
            // No hay evento en Calendar, solo actualizar estado local
            $meeting->update(['status' => 'cancelled']);
            return;
        }

        $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $service = $this->getCalendarService($config);

        $service->events->delete('primary', $meeting->google_event_id, [
            'sendUpdates' => 'all',
        ]);

        $meeting->update(['status' => 'cancelled']);

        Log::info('✅ Evento cancelado en Google Calendar', [
            'user_id'        => $user->id,
            'meeting_id'     => $meeting->id,
            'google_event_id'=> $meeting->google_event_id,
        ]);
    }

    // ─── Vista Calendar ──────────────────────────────────────────────────────

    /**
     * Devuelve los eventos de Google Calendar en un rango de fechas.
     * Usado por la vista /vera/calendar.
     *
     * @return array  Array de ['id', 'title', 'start', 'end', 'meet_link', 'source']
     */
    public function getUpcomingEvents(User $user, Carbon $from, Carbon $to): array
    {
        $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
        $service = $this->getCalendarService($config);

        $results = $service->events->listEvents('primary', [
            'timeMin'      => $from->startOfDay()->toRfc3339String(),
            'timeMax'      => $to->endOfDay()->toRfc3339String(),
            'singleEvents' => true,
            'orderBy'      => 'startTime',
            'maxResults'   => 100,
        ]);

        $events = [];
        foreach ($results->getItems() as $event) {
            $startRaw = $event->getStart()->getDateTime() ?? $event->getStart()->getDate();
            $endRaw   = $event->getEnd()->getDateTime()   ?? $event->getEnd()->getDate();

            $start = Carbon::parse($startRaw);
            $end   = Carbon::parse($endRaw);

            // Buscar Meeting en BD para saber si fue propuesto por Vera
            $meeting = Meeting::where('user_id', $user->id)
                ->where('google_event_id', $event->getId())
                ->first();

            $events[] = [
                'id'           => $event->getId(),
                'meeting_id'   => $meeting?->id,
                'title'        => $event->getSummary() ?? '(sin título)',
                'start'        => $start,
                'end'          => $end,
                'meet_link'    => $event->getHangoutLink(),
                'source'       => $meeting?->source_channel ?? 'manual',
                'contact_name' => $meeting?->contact_name,
                'contact_email'=> $meeting?->contact_email,
                'duration_min' => $start->diffInMinutes($end),
                'status'       => $meeting?->status ?? 'confirmed',
            ];
        }

        return $events;
    }

    /**
     * Devuelve el número de eventos para hoy (para el badge del sidebar).
     */
    public function getTodayEventCount(User $user): int
    {
        try {
            $config  = VeraConfig::where('user_id', $user->id)->first();
            if (!$config?->hasCalendarConnected()) return 0;

            $service = $this->getCalendarService($config);
            $results = $service->events->listEvents('primary', [
                'timeMin'      => now()->startOfDay()->toRfc3339String(),
                'timeMax'      => now()->endOfDay()->toRfc3339String(),
                'singleEvents' => true,
                'maxResults'   => 50,
            ]);

            return count($results->getItems());
        } catch (\Exception) {
            return 0;
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Verifica si un slot propuesto no solapa con ningún bloque ocupado (con buffer).
     */
    private function isSlotFree(Carbon $start, Carbon $end, array $busyBlocks, int $bufferMinutes): bool
    {
        $startWithBuffer = $start->copy()->subMinutes($bufferMinutes);
        $endWithBuffer   = $end->copy()->addMinutes($bufferMinutes);

        foreach ($busyBlocks as $block) {
            // Solapamiento: el slot toca el bloque (incluyendo buffer)
            if ($startWithBuffer->lessThan($block['end']) && $endWithBuffer->greaterThan($block['start'])) {
                return false;
            }
        }

        return true;
    }
}
