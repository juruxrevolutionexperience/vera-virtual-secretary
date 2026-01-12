<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContactService
{
    /**
     * Sincronizar contactos desde emails del usuario
     */
    public function syncFromEmails(User $user): array
    {
        $stats = [
            'processed' => 0,
            'created' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            // Obtener todos los emails del usuario
            $emails = Email::where('user_id', $user->id)->get();

            foreach ($emails as $email) {
                try {
                    // Sincronizar remitente
                    if ($email->from_email) {
                        $this->syncContactFromEmail($user, $email, 'from');
                        $stats['processed']++;
                    }

                    // Sincronizar destinatarios (to)
                    if (is_array($email->to)) {
                        foreach ($email->to as $recipient) {
                            $this->syncContactFromRecipient($user, $recipient, $email, 'to');
                            $stats['processed']++;
                        }
                    }

                    // Sincronizar CC
                    if (is_array($email->cc)) {
                        foreach ($email->cc as $cc) {
                            $this->syncContactFromRecipient($user, $cc, $email, 'cc');
                            $stats['processed']++;
                        }
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error('Error syncing contact from email', [
                        'email_id' => $email->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $stats['created'] = Contact::where('user_id', $user->id)
                ->where('created_at', '>=', now()->subMinutes(5))
                ->count();

            $stats['updated'] = $stats['processed'] - $stats['created'];

        } catch (\Exception $e) {
            Log::error('Error in syncFromEmails', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $stats;
    }

    /**
     * Sincronizar contacto desde un email (remitente)
     */
    protected function syncContactFromEmail(User $user, Email $email, string $direction): ?Contact
    {
        $contactEmail = $email->from_email;
        $contactName = $email->from_name;

        if (!$contactEmail || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $contact = Contact::firstOrCreate(
            [
                'user_id' => $user->id,
                'email' => $contactEmail,
            ],
            [
                'name' => $contactName,
                'first_contact_at' => $email->received_at ?? now(),
            ]
        );

        // Actualizar nombre si está vacío o si el nuevo es mejor
        if (!$contact->name && $contactName) {
            $contact->update(['name' => $contactName]);
        }

        // Incrementar contador según dirección
        if ($direction === 'from') {
            $contact->incrementReceived();
        } else {
            $contact->incrementSent();
        }

        return $contact;
    }

    /**
     * Sincronizar contacto desde un destinatario (to/cc/bcc)
     */
    protected function syncContactFromRecipient(User $user, $recipient, Email $email, string $type): ?Contact
    {
        // El recipient puede ser string o array ['email' => '', 'name' => '']
        $contactEmail = is_array($recipient) ? ($recipient['email'] ?? $recipient['address'] ?? null) : $recipient;
        $contactName = is_array($recipient) ? ($recipient['name'] ?? null) : null;

        if (!$contactEmail || !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        $contact = Contact::firstOrCreate(
            [
                'user_id' => $user->id,
                'email' => $contactEmail,
            ],
            [
                'name' => $contactName,
                'first_contact_at' => $email->received_at ?? now(),
            ]
        );

        // Actualizar nombre si está vacío o si el nuevo es mejor
        if (!$contact->name && $contactName) {
            $contact->update(['name' => $contactName]);
        }

        // Incrementar contador de emails enviados
        $contact->incrementSent();

        return $contact;
    }

    /**
     * Obtener o crear contacto
     */
    public function getOrCreate(User $user, string $email, ?string $name = null): Contact
    {
        return Contact::firstOrCreate(
            [
                'user_id' => $user->id,
                'email' => $email,
            ],
            [
                'name' => $name,
                'first_contact_at' => now(),
            ]
        );
    }

    /**
     * Actualizar contacto
     */
    public function updateContact(Contact $contact, array $data): Contact
    {
        $contact->update($data);
        return $contact->fresh();
    }

    /**
     * Buscar contactos
     */
    public function search(User $user, string $query, int $limit = 20): Collection
    {
        return Contact::where('user_id', $user->id)
            ->search($query)
            ->notBlocked()
            ->orderBy('last_contact_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener contactos frecuentes
     */
    public function getFrequentContacts(User $user, int $threshold = 10, int $limit = 20): Collection
    {
        return Contact::where('user_id', $user->id)
            ->notBlocked()
            ->where(DB::raw('emails_received + emails_sent'), '>=', $threshold)
            ->orderByRaw('emails_received + emails_sent DESC')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener contactos recientes
     */
    public function getRecentContacts(User $user, int $days = 30, int $limit = 20): Collection
    {
        return Contact::where('user_id', $user->id)
            ->notBlocked()
            ->recent($days)
            ->orderBy('last_contact_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obtener contactos VIP
     */
    public function getVipContacts(User $user): Collection
    {
        return Contact::where('user_id', $user->id)
            ->vip()
            ->notBlocked()
            ->orderBy('last_contact_at', 'desc')
            ->get();
    }

    /**
     * Obtener contactos inactivos
     */
    public function getInactiveContacts(User $user, int $days = 90): Collection
    {
        return Contact::where('user_id', $user->id)
            ->notBlocked()
            ->where(function ($q) use ($days) {
                $q->whereNull('last_contact_at')
                    ->orWhere('last_contact_at', '<', now()->subDays($days));
            })
            ->orderBy('last_contact_at', 'desc')
            ->get();
    }

    /**
     * Obtener estadísticas de contactos
     */
    public function getStats(User $user): array
    {
        $total = Contact::where('user_id', $user->id)->count();
        $vip = Contact::where('user_id', $user->id)->vip()->count();
        $starred = Contact::where('user_id', $user->id)->starred()->count();
        $blocked = Contact::where('user_id', $user->id)->blocked()->count();
        $recent = Contact::where('user_id', $user->id)->recent(30)->count();
        $frequent = Contact::where('user_id', $user->id)
            ->where(DB::raw('emails_received + emails_sent'), '>=', 10)
            ->count();

        return [
            'total' => $total,
            'vip' => $vip,
            'starred' => $starred,
            'blocked' => $blocked,
            'recent_30_days' => $recent,
            'frequent' => $frequent,
        ];
    }

    /**
     * Bloquear contacto
     */
    public function blockContact(Contact $contact): Contact
    {
        $contact->block();
        return $contact->fresh();
    }

    /**
     * Desbloquear contacto
     */
    public function unblockContact(Contact $contact): Contact
    {
        $contact->unblock();
        return $contact->fresh();
    }

    /**
     * Marcar contacto como VIP
     */
    public function markAsVip(Contact $contact): Contact
    {
        $contact->setPriority('vip');
        return $contact->fresh();
    }

    /**
     * Eliminar contacto
     */
    public function deleteContact(Contact $contact): bool
    {
        return $contact->delete();
    }

    /**
     * Limpiar contactos duplicados
     */
    public function cleanDuplicates(User $user): array
    {
        $duplicates = Contact::select('email', DB::raw('COUNT(*) as count'))
            ->where('user_id', $user->id)
            ->groupBy('email')
            ->having('count', '>', 1)
            ->get();

        $stats = [
            'duplicates_found' => $duplicates->count(),
            'contacts_merged' => 0,
            'contacts_deleted' => 0,
        ];

        foreach ($duplicates as $duplicate) {
            $contacts = Contact::where('user_id', $user->id)
                ->where('email', $duplicate->email)
                ->orderBy('created_at')
                ->get();

            // Mantener el primero, fusionar datos
            $main = $contacts->first();
            $toMerge = $contacts->slice(1);

            foreach ($toMerge as $contact) {
                // Sumar estadísticas
                $main->increment('emails_received', $contact->emails_received);
                $main->increment('emails_sent', $contact->emails_sent);

                // Actualizar fechas si es necesario
                if ($contact->first_contact_at && (!$main->first_contact_at || $contact->first_contact_at < $main->first_contact_at)) {
                    $main->first_contact_at = $contact->first_contact_at;
                }

                if ($contact->last_contact_at && (!$main->last_contact_at || $contact->last_contact_at > $main->last_contact_at)) {
                    $main->last_contact_at = $contact->last_contact_at;
                }

                // Actualizar nombre si el duplicado tiene uno mejor
                if (!$main->name && $contact->name) {
                    $main->name = $contact->name;
                }

                $main->save();

                // Eliminar duplicado
                $contact->delete();
                $stats['contacts_deleted']++;
            }

            $stats['contacts_merged']++;
        }

        return $stats;
    }
}
