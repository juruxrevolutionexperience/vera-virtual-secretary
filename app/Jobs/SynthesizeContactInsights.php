<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\VeraConversation;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SynthesizeContactInsights
 *
 * Analiza la conversación reciente y actualiza vera_insights en Contact.metadata.
 * Se despacha de forma asíncrona al final de cada ProcessIncomingMessage para
 * mantener el perfil del cliente actualizado sin que el dueño tenga que hacerlo
 * manualmente.
 *
 * Canal email   → busca Contact por email
 * Canal telegram → busca Contact por telegram_id (ya creado por ProcessIncomingMessage)
 */
class SynthesizeContactInsights implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 45;

    public function __construct(
        private readonly int    $userId,
        private readonly string $channel,
        private readonly string $contactIdentifier,
        private readonly int    $conversationId,
    ) {}

    public function handle(): void
    {
        $aiServiceUrl = config('services.ai_service.url');
        if (!$aiServiceUrl) {
            return;
        }

        $user = User::find($this->userId);
        if (!$user) {
            return;
        }

        $contact = $this->findContact($user);
        if (!$contact) {
            return;
        }

        $history = $this->loadHistory();
        if (count($history) < 2) {
            // Con menos de 2 mensajes no hay suficiente contexto para sintetizar
            return;
        }

        $existingInsights = $contact->metadata['vera_insights'] ?? null;

        try {
            $response = Http::timeout(40)
                ->withHeaders(['X-Service-Token' => config('services.ai_service.secret')])
                ->post("{$aiServiceUrl}/synthesize", [
                    'user_id'           => (string) $this->userId,
                    'history'           => $history,
                    'existing_insights' => $existingInsights,
                ]);

            if (!$response->successful()) {
                Log::warning('SynthesizeContactInsights: ai-service respondió con error', [
                    'status'  => $response->status(),
                    'contact' => $this->contactIdentifier,
                ]);
                return;
            }

            $newInsights = $response->json('vera_insights');
            if (!$newInsights) {
                return;
            }

            $merged  = $this->mergeInsights($existingInsights ?? [], $newInsights);
            $metadata = $contact->metadata ?? [];
            $metadata['vera_insights'] = $merged;
            $contact->update(['metadata' => $metadata]);

            Log::info('🧠 vera_insights actualizados', [
                'user_id'    => $this->userId,
                'contact_id' => $contact->id,
                'channel'    => $this->channel,
                'topics'     => $merged['past_topics'] ?? [],
            ]);
        } catch (\Exception $e) {
            Log::warning('SynthesizeContactInsights: excepción', [
                'error'   => $e->getMessage(),
                'contact' => $this->contactIdentifier,
            ]);
        }
    }

    private function findContact(User $user): ?Contact
    {
        if ($this->channel === 'telegram') {
            return Contact::where('user_id', $user->id)
                ->where('telegram_id', $this->contactIdentifier)
                ->first();
        }

        // Email — extraer solo el email si viene como "Nombre <email>"
        $email = preg_match('/<(.+?)>/', $this->contactIdentifier, $m)
            ? strtolower(trim($m[1]))
            : strtolower(trim($this->contactIdentifier));

        return Contact::where('user_id', $user->id)
            ->where('email', $email)
            ->first();
    }

    private function loadHistory(): array
    {
        return VeraConversation::find($this->conversationId)
            ?->messages()
            ->orderBy('created_at', 'desc')
            ->limit(15)
            ->get()
            ->reverse()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->values()
            ->toArray() ?? [];
    }

    /**
     * Combina insights existentes con los nuevos del LLM.
     *
     * - last_interaction_summary: siempre el nuevo (es el resumen de esta conversación)
     * - communication_style: el nuevo si no es null, si no conserva el anterior
     * - past_topics / preferred_times / quirks: merge + dedup (acumulativos)
     */
    private function mergeInsights(array $existing, array $new): array
    {
        return [
            'last_interaction_summary' => $new['last_interaction_summary']
                ?? $existing['last_interaction_summary']
                ?? null,

            'communication_style' => $new['communication_style']
                ?? $existing['communication_style']
                ?? null,

            'past_topics'     => $this->mergeArray($existing['past_topics']     ?? [], $new['past_topics']     ?? []),
            'preferred_times' => $this->mergeArray($existing['preferred_times'] ?? [], $new['preferred_times'] ?? []),
            'quirks'          => $this->mergeArray($existing['quirks']          ?? [], $new['quirks']          ?? []),
        ];
    }

    private function mergeArray(mixed $a, mixed $b): ?array
    {
        $arrA = is_array($a) ? $a : ($a ? [$a] : []);
        $arrB = is_array($b) ? $b : ($b ? [$b] : []);
        $merged = array_values(array_unique(array_merge($arrA, $arrB)));
        return empty($merged) ? null : $merged;
    }
}
