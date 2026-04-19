<?php

namespace App\Services;

use App\Models\User;
use App\Models\VeraConfig;
use App\Models\Meeting;
use App\Traits\GoogleOAuthTrait;
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
    use GoogleOAuthTrait;

    private GoogleClient $client;

    public function __construct()
    {
        $this->client = new GoogleClient();
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setAccessType('offline');
    }

    // ─── Autenticación ────────────────────────────────────────────────────────

    private function getCalendarService(VeraConfig $config): Calendar
    {
        $this->refreshGoogleToken($this->client, $config);
        return new Calendar($this->client);
    }

    // ─── Disponibilidad ───────────────────────────────────────────────────────

    /**
     * Obtiene slots disponibles para los próximos N días.
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

        try {
            $service      = $this->getCalendarService($config);
            $calendarId   = 'primary';
            $duration     = $config->meeting_duration ?? 45;
            $buffer       = $config->buffer_minutes ?? 15;
            $workingHours = $config->working_hours ?? [];

            $start = $preferredDate
                ? Carbon::parse($preferredDate)->startOfDay()
                : now()->addHour()->roundMinutes(15);

            $end = $start->copy()->addDays($daysAhead)->endOfDay();

            $existingEvents = $service->events->listEvents($calendarId, [
                'timeMin'      => $start->toRfc3339String(),
                'timeMax'      => $end->toRfc3339String(),
                'singleEvents' => true,
                'orderBy'      => 'startTime',
            ]);

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

            $slots   = [];
            $current = $start->copy();

            $dayMap = [0 => 'sunday', 1 => 'monday', 2 => 'tuesday', 3 => 'wednesday', 4 => 'thursday', 5 => 'friday', 6 => 'saturday'];

            while ($current->lessThan($end) && count($slots) < $maxSlots) {
                $dayKey    = $dayMap[$current->dayOfWeek];
                $dayConfig = $workingHours[$dayKey] ?? null;

                if (!empty($dayConfig) && ($dayConfig['enabled'] ?? false)) {
                    $dayStart = $dayConfig['start'] ?? '09:00';
                    $dayEnd   = $dayConfig['end']   ?? '18:00';

                    $slotStart  = $current->copy()->setTimeFromTimeString($dayStart . ':00');
                    $dayEndTime = $current->copy()->setTimeFromTimeString($dayEnd . ':00');

                    if ($slotStart->lessThan(now()->addMinutes(30))) {
                        $slotStart = now()->addMinutes(30)->roundMinutes(15);
                    }

                    while ($slotStart->copy()->addMinutes($duration)->lessThanOrEqualTo($dayEndTime)) {
                        $slotEnd = $slotStart->copy()->addMinutes($duration);

                        if ($this->isSlotFree($slotStart, $slotEnd, $busyBlocks, $buffer)) {
                            $slots[] = [
                                'start' => $slotStart->copy(),
                                'end'   => $slotEnd->copy(),
                                'label' => $slotStart->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i'),
                            ];

                            if (count($slots) >= $maxSlots) {
                                break 2;
                            }
                        }

                        $slotStart->addMinutes(30);
                    }
                }

                $current->addDay()->startOfDay();
            }

            return $slots;
        } catch (\Google\Service\Exception $e) {
            $this->logGoogleError('getAvailableSlots', $user->id, $e);
            throw $this->wrapGoogleException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: error inesperado en getAvailableSlots', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error inesperado consultando Google Calendar.', 0, $e);
        }
    }

    /**
     * Verifica si un slot concreto está libre (sin conflictos).
     */
    public function checkConflicts(User $user, Carbon $start, Carbon $end): bool
    {
        try {
            $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
            $service = $this->getCalendarService($config);

            $events = $service->events->listEvents('primary', [
                'timeMin'      => $start->toRfc3339String(),
                'timeMax'      => $end->toRfc3339String(),
                'singleEvents' => true,
            ]);

            return count($events->getItems()) === 0;
        } catch (\Google\Service\Exception $e) {
            $this->logGoogleError('checkConflicts', $user->id, $e);
            throw $this->wrapGoogleException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: error inesperado en checkConflicts', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error inesperado verificando conflictos en Google Calendar.', 0, $e);
        }
    }

    // ─── CRUD de eventos ──────────────────────────────────────────────────────

    /**
     * Crea un evento en Google Calendar y actualiza el modelo Meeting.
     *
     * @return string  google_event_id del evento creado
     */
    public function createEvent(User $user, Meeting $meeting): string
    {
        try {
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
                'sendUpdates' => 'all',
            ]);

            $googleEventId = $createdEvent->getId();
            $meetLink      = $createdEvent->getHangoutLink();

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
        } catch (\Google\Service\Exception $e) {
            $this->logGoogleError('createEvent', $user->id, $e, ['meeting_id' => $meeting->id]);
            throw $this->wrapGoogleException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: error inesperado en createEvent', [
                'user_id'    => $user->id,
                'meeting_id' => $meeting->id,
                'error'      => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error inesperado creando evento en Google Calendar.', 0, $e);
        }
    }

    /**
     * Modifica fecha/hora de un evento existente.
     */
    public function updateEvent(User $user, Meeting $meeting, Carbon $newStart): void
    {
        if (!$meeting->google_event_id) {
            throw new \RuntimeException("Meeting #{$meeting->id} no tiene google_event_id.");
        }

        try {
            $config  = VeraConfig::where('user_id', $user->id)->firstOrFail();
            $service = $this->getCalendarService($config);

            $event  = $service->events->get('primary', $meeting->google_event_id);
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
        } catch (\Google\Service\Exception $e) {
            $this->logGoogleError('updateEvent', $user->id, $e, [
                'meeting_id'     => $meeting->id,
                'google_event_id'=> $meeting->google_event_id,
            ]);
            throw $this->wrapGoogleException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: error inesperado en updateEvent', [
                'user_id'    => $user->id,
                'meeting_id' => $meeting->id,
                'error'      => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error inesperado actualizando evento en Google Calendar.', 0, $e);
        }
    }

    /**
     * Cancela un evento en Google Calendar.
     */
    public function cancelEvent(User $user, Meeting $meeting): void
    {
        if (!$meeting->google_event_id) {
            $meeting->update(['status' => 'cancelled']);
            return;
        }

        try {
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
        } catch (\Google\Service\Exception $e) {
            // Si el evento ya no existe en Calendar (404), cancelamos localmente igualmente
            if ($e->getCode() === 404) {
                $meeting->update(['status' => 'cancelled']);
                Log::warning('GoogleCalendar: evento no encontrado al cancelar, cancelado localmente', [
                    'user_id'        => $user->id,
                    'meeting_id'     => $meeting->id,
                    'google_event_id'=> $meeting->google_event_id,
                ]);
                return;
            }
            $this->logGoogleError('cancelEvent', $user->id, $e, ['meeting_id' => $meeting->id]);
            throw $this->wrapGoogleException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: error inesperado en cancelEvent', [
                'user_id'    => $user->id,
                'meeting_id' => $meeting->id,
                'error'      => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error inesperado cancelando evento en Google Calendar.', 0, $e);
        }
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
        try {
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
        } catch (\Google\Service\Exception $e) {
            $this->logGoogleError('getUpcomingEvents', $user->id, $e);
            throw $this->wrapGoogleException($e);
        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('GoogleCalendar: error inesperado en getUpcomingEvents', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
            throw new \RuntimeException('Error inesperado obteniendo eventos de Google Calendar.', 0, $e);
        }
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

    private function isSlotFree(Carbon $start, Carbon $end, array $busyBlocks, int $bufferMinutes): bool
    {
        $startWithBuffer = $start->copy()->subMinutes($bufferMinutes);
        $endWithBuffer   = $end->copy()->addMinutes($bufferMinutes);

        foreach ($busyBlocks as $block) {
            if ($startWithBuffer->lessThan($block['end']) && $endWithBuffer->greaterThan($block['start'])) {
                return false;
            }
        }

        return true;
    }

    private function logGoogleError(string $method, int $userId, \Google\Service\Exception $e, array $extra = []): void
    {
        Log::error("GoogleCalendar: API error en {$method}", array_merge([
            'user_id' => $userId,
            'code'    => $e->getCode(),
            'error'   => $e->getMessage(),
        ], $extra));
    }

    private function wrapGoogleException(\Google\Service\Exception $e): \RuntimeException
    {
        return match (true) {
            in_array($e->getCode(), [401, 403]) => new \RuntimeException(
                'Google Calendar: acceso denegado. Reconecta tu cuenta de Google.', $e->getCode(), $e
            ),
            $e->getCode() === 429 => new \RuntimeException(
                'Google Calendar: límite de peticiones alcanzado. Inténtalo en unos minutos.', 429, $e
            ),
            $e->getCode() === 404 => new \RuntimeException(
                'Google Calendar: recurso no encontrado.', 404, $e
            ),
            default => new \RuntimeException(
                'Error de Google Calendar API: ' . $e->getMessage(), $e->getCode(), $e
            ),
        };
    }
}
