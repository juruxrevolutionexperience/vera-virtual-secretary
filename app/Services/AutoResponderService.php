<?php

namespace App\Services;

use App\Models\AutoResponder;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AutoResponderService
{
    /**
     * Crear un nuevo auto-responder
     */
    public function createAutoResponder(User $user, array $data): AutoResponder
    {
        $autoResponder = AutoResponder::create([
            'user_id' => $user->id,
            'email_account_id' => $data['email_account_id'] ?? null,
            'name' => $data['name'],
            'type' => $data['type'] ?? 'custom',
            'email_template_id' => $data['email_template_id'] ?? null,
            'subject' => $data['subject'] ?? null,
            'body' => $data['body'] ?? null,
            'is_active' => $data['is_active'] ?? false,
            'active_from' => $data['active_from'] ?? null,
            'active_until' => $data['active_until'] ?? null,
            'sender_filters' => $data['sender_filters'] ?? null,
            'subject_filters' => $data['subject_filters'] ?? null,
            'only_first_contact' => $data['only_first_contact'] ?? true,
            'max_responses_per_sender' => $data['max_responses_per_sender'] ?? 1,
            'max_responses_per_day' => $data['max_responses_per_day'] ?? null,
            'delay_minutes' => $data['delay_minutes'] ?? 0,
            'schedule' => $data['schedule'] ?? null,
            'reply_to_all' => $data['reply_to_all'] ?? false,
            'skip_auto_replies' => $data['skip_auto_replies'] ?? true,
        ]);

        Log::info('🤖 Auto-responder creado', [
            'auto_responder_id' => $autoResponder->id,
            'name' => $autoResponder->name,
            'type' => $autoResponder->type,
            'user_id' => $user->id,
        ]);

        return $autoResponder;
    }

    /**
     * Actualizar auto-responder
     */
    public function updateAutoResponder(AutoResponder $autoResponder, array $data): AutoResponder
    {
        $autoResponder->update($data);

        Log::info('✏️ Auto-responder actualizado', [
            'auto_responder_id' => $autoResponder->id,
        ]);

        return $autoResponder->fresh();
    }

    /**
     * Eliminar auto-responder
     */
    public function deleteAutoResponder(AutoResponder $autoResponder): void
    {
        $autoResponderId = $autoResponder->id;
        $autoResponderName = $autoResponder->name;

        $autoResponder->delete();

        Log::info('🗑️ Auto-responder eliminado', [
            'auto_responder_id' => $autoResponderId,
            'name' => $autoResponderName,
        ]);
    }

    /**
     * Activar/Desactivar auto-responder
     */
    public function toggleActive(AutoResponder $autoResponder): AutoResponder
    {
        $autoResponder->update(['is_active' => !$autoResponder->is_active]);

        Log::info('🔄 Auto-responder ' . ($autoResponder->is_active ? 'activado' : 'desactivado'), [
            'auto_responder_id' => $autoResponder->id,
        ]);

        return $autoResponder;
    }

    /**
     * Obtener auto-responders del usuario
     */
    public function getUserAutoResponders(User $user, ?bool $onlyActive = null): Collection
    {
        $query = AutoResponder::forUser($user->id)
            ->with(['template', 'emailAccount']);

        if ($onlyActive === true) {
            $query->active();
        } elseif ($onlyActive === false) {
            $query->where('is_active', false);
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Procesar email entrante para auto-respuestas
     */
    public function processIncomingEmail(Email $email): void
    {
        Log::info('🔍 Procesando email para auto-respuestas', [
            'email_id' => $email->id,
            'from' => $email->from_email,
        ]);

        // Obtener auto-responders activos del usuario
        $autoResponders = AutoResponder::forUser($email->user_id)
            ->active()
            ->get();

        foreach ($autoResponders as $autoResponder) {
            if ($autoResponder->shouldRespondTo($email)) {
                $this->sendAutoResponse($autoResponder, $email);
            }
        }
    }

    /**
     * Enviar auto-respuesta
     */
    protected function sendAutoResponse(AutoResponder $autoResponder, Email $email): void
    {
        try {
            Log::info('📤 Enviando auto-respuesta', [
                'auto_responder_id' => $autoResponder->id,
                'email_id' => $email->id,
            ]);

            // Obtener contenido de la respuesta
            $content = $autoResponder->getResponseContent($email);

            // Aquí iría la integración con el servicio de envío de emails
            // Por ahora lo simulamos

            Log::info('Auto-respuesta enviada', [
                'to' => $email->from_email,
                'subject' => $content['subject'],
            ]);

            // Registrar en el historial
            $autoResponder->recordResponse($email);

            Log::info('✅ Auto-respuesta enviada exitosamente', [
                'auto_responder_id' => $autoResponder->id,
                'email_id' => $email->id,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error enviando auto-respuesta', [
                'auto_responder_id' => $autoResponder->id,
                'email_id' => $email->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Crear auto-responder de "Fuera de oficina"
     */
    public function createOutOfOfficeResponder(
        User $user,
        string $message,
        \DateTime $startDate,
        \DateTime $endDate
    ): AutoResponder {
        return $this->createAutoResponder($user, [
            'name' => 'Fuera de Oficina',
            'type' => 'out_of_office',
            'subject' => 'Fuera de Oficina - Out of Office',
            'body' => $message,
            'is_active' => true,
            'active_from' => $startDate,
            'active_until' => $endDate,
            'only_first_contact' => true,
            'max_responses_per_sender' => 1,
            'skip_auto_replies' => true,
        ]);
    }

    /**
     * Obtener estadísticas de auto-responders
     */
    public function getStats(User $user): array
    {
        $autoResponders = AutoResponder::forUser($user->id)->get();

        return [
            'total' => $autoResponders->count(),
            'active' => $autoResponders->where('is_active', true)->count(),
            'inactive' => $autoResponders->where('is_active', false)->count(),
            'by_type' => $autoResponders->groupBy('type')->map->count(),
            'total_responses' => $autoResponders->sum('responses_sent'),
            'most_used' => $autoResponders->sortByDesc('responses_sent')->first(),
        ];
    }

    /**
     * Limpiar historial antiguo
     */
    public function cleanupOldHistory(int $daysOld = 90): int
    {
        $deleted = \App\Models\AutoResponderHistory::where('responded_at', '<', now()->subDays($daysOld))
            ->delete();

        Log::info('🧹 Historial de auto-respuestas antiguo eliminado', [
            'count' => $deleted,
            'days_old' => $daysOld,
        ]);

        return $deleted;
    }
}
