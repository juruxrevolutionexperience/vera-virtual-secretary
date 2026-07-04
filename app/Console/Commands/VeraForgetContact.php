<?php

namespace App\Console\Commands;

use App\Models\Contact;
use App\Models\DataRetentionLog;
use App\Models\Meeting;
use App\Models\MeetingRequest;
use App\Models\VeraActivityLog;
use App\Models\VeraConversationMessage;
use Illuminate\Console\Command;

/**
 * Derecho de supresión RGPD para CLIENTES FINALES (contactos de un negocio).
 *
 * Cuando el cliente de un negocio (paciente, lead...) pide que se borren sus
 * datos, el dueño nos lo comunica y ejecutamos este comando. Borra el contacto
 * y anonimiza todo rastro personal: conversaciones, solicitudes de cita,
 * citas (se conserva el registro de la cita sin identidad) y activity logs.
 *
 * Uso:  php artisan vera:forget-contact 123
 *       php artisan vera:forget-contact 123 --dry-run
 */
class VeraForgetContact extends Command
{
    protected $signature = 'vera:forget-contact
                            {contact : ID del contacto en la tabla contacts}
                            {--dry-run : Mostrar qué se anonimizaría sin tocar nada}';

    protected $description = 'RGPD: suprime todos los datos personales de un cliente final (contacto)';

    private const PLACEHOLDER = '[eliminado a petición del interesado — RGPD]';

    public function handle(): int
    {
        $dryRun  = (bool) $this->option('dry-run');
        $contact = Contact::find($this->argument('contact'));

        if (!$contact) {
            $this->error('Contacto no encontrado.');
            return 1;
        }

        $userId = $contact->user_id;

        // Identificadores personales a rastrear en el resto de tablas
        $identifiers = array_values(array_filter([
            $contact->email,
            $contact->phone,
            $contact->telegram_id,
        ]));
        $contactName = $contact->name;

        $this->info("Contacto #{$contact->id} del negocio user #{$userId}");
        $this->line('Identificadores a suprimir: ' . count($identifiers));
        if ($dryRun) {
            $this->warn('[DRY-RUN] No se modificará nada');
        }
        $this->newLine();

        // ── 1. Solicitudes de cita del contacto ──────────────────────────────
        $requests = MeetingRequest::where('user_id', $userId)
            ->where(function ($q) use ($contact, $identifiers) {
                $q->where('contact_id', $contact->id);
                foreach ($identifiers as $id) {
                    $q->orWhere('contact_identifier', $id)
                      ->orWhere('contact_identifier', 'like', '%<' . $id . '>%');
                }
            })
            ->get();

        $conversationIds = $requests->pluck('conversation_id')->filter()->unique()->values();
        $requestIds      = $requests->pluck('id');

        $this->line("Solicitudes de cita: {$requests->count()}");
        $this->line("Conversaciones vinculadas: {$conversationIds->count()}");

        // ── 2. Mensajes de conversación (contenido personal) ─────────────────
        $msgQuery = VeraConversationMessage::whereIn('conversation_id', $conversationIds);
        $msgCount = $msgQuery->count();
        $this->line("Mensajes de conversación: {$msgCount}");

        // ── 3. Citas (se conserva la cita como registro, sin identidad) ──────
        $meetingsQuery = Meeting::where('user_id', $userId)
            ->where(function ($q) use ($contact, $identifiers, $requestIds, $contactName) {
                $q->where('contact_id', $contact->id)
                  ->orWhereIn('meeting_request_id', $requestIds);
                foreach ($identifiers as $id) {
                    $q->orWhere('contact_email', $id)
                      ->orWhere('contact_phone', $id)
                      ->orWhere('contact_email', 'like', '%<' . $id . '>%');
                }
                if ($contactName) {
                    $q->orWhere('contact_name', $contactName);
                }
            });
        $meetingCount = $meetingsQuery->count();
        $this->line("Citas a anonimizar: {$meetingCount}");

        // ── 4. Activity logs que mencionan al contacto ───────────────────────
        $logQuery = VeraActivityLog::where('user_id', $userId)
            ->where(function ($q) use ($identifiers, $contactName) {
                foreach ($identifiers as $id) {
                    $q->orWhere('title', 'like', '%' . $id . '%')
                      ->orWhere('description', 'like', '%' . $id . '%')
                      ->orWhere('metadata', 'like', '%' . $id . '%');
                }
                if ($contactName) {
                    $q->orWhere('title', 'like', '%' . $contactName . '%');
                }
            });
        $logCount = $logQuery->count();
        $this->line("Activity logs a limpiar: {$logCount}");
        $this->newLine();

        if ($dryRun) {
            $this->info('[DRY-RUN] Fin. Nada modificado.');
            return 0;
        }

        // ── Ejecución ─────────────────────────────────────────────────────────
        $msgQuery->update(['content' => self::PLACEHOLDER, 'metadata' => null]);

        foreach ($requests as $req) {
            $req->update([
                'contact_identifier'  => 'suprimido-rgpd-' . $contact->id,
                'contact_id'          => null,
                'context'             => null,
                'booking_token'       => null,
                'cancel_token'        => null,
            ]);
        }

        $meetingsQuery->update([
            'contact_name'  => self::PLACEHOLDER,
            'contact_email' => null,
            'contact_phone' => null,
            'contact_id'    => null,
            'notes'         => null,
        ]);

        foreach ($logQuery->get() as $log) {
            $title = $log->title;
            $desc  = $log->description;
            foreach (array_merge($identifiers, array_filter([$contactName])) as $needle) {
                $title = $title !== null ? str_replace($needle, self::PLACEHOLDER, $title) : null;
                $desc  = $desc !== null ? str_replace($needle, self::PLACEHOLDER, $desc) : null;
            }
            $meta = $log->metadata;
            if (is_array($meta) && isset($meta['contact'])) {
                $meta['contact'] = self::PLACEHOLDER;
            }
            $log->update(['title' => $title, 'description' => $desc, 'metadata' => $meta]);
        }

        $contactId = $contact->id;
        $contact->delete();

        // Registro de cumplimiento — evidencia de que se atendió la solicitud
        DataRetentionLog::create([
            'user_id'          => $userId,
            'data_type'        => 'contact_erasure',
            'records_deleted'  => 1 + $msgCount + $meetingCount + $logCount + $requests->count(),
            'retention_period' => 'a petición del interesado',
            'trigger'          => 'gdpr_erasure_request',
            'metadata'         => [
                'contact_id'    => $contactId,
                'messages'      => $msgCount,
                'meetings'      => $meetingCount,
                'requests'      => $requests->count(),
                'activity_logs' => $logCount,
            ],
            'deleted_at'       => now(),
        ]);

        $this->info("✅ Contacto #{$contactId} suprimido y rastro anonimizado.");
        $this->line('Registrado en data_retention_logs como evidencia de cumplimiento.');

        return 0;
    }
}
