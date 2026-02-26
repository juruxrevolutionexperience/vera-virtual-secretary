<?php

namespace App\Services;

use App\Models\EmailAccount;
use App\Models\VeraConfig;
use App\Services\AI\AIGatewayService;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Illuminate\Support\Facades\Log;

/**
 * VeraWritingStyleService
 *
 * Analiza los emails enviados del usuario desde Gmail (SENT folder)
 * y extrae su perfil de escritura real.
 * El resultado se guarda en vera_configs.writing_style para que
 * buildSystemPrompt() lo use automáticamente en todas las respuestas.
 */
class VeraWritingStyleService
{
    protected AIGatewayService $ai;

    public function __construct()
    {
        $this->ai = new AIGatewayService();
    }

    /**
     * Proceso completo: fetch enviados → analizar → guardar
     * Retorna array con el perfil extraído y stats del análisis
     */
    public function analyzeAndSave(int $userId): array
    {
        $account = EmailAccount::where('user_id', $userId)
            ->where('sync_enabled', true)
            ->first();

        if (!$account) {
            throw new \RuntimeException('No hay cuenta de email conectada para este usuario.');
        }

        // 1. Obtener emails enviados de Gmail
        $sentEmails = $this->fetchSentEmailsFromGmail($account, 60);

        if (count($sentEmails) < 3) {
            throw new \RuntimeException('No se encontraron suficientes emails enviados (mínimo 3). Envía algunos emails primero.');
        }

        Log::info('✍️ VeraWritingStyle: emails enviados obtenidos', [
            'user_id'  => $userId,
            'count'    => count($sentEmails),
        ]);

        // 2. Analizar con Gemini
        $profile = $this->analyzeWithGemini($sentEmails, $userId);

        // 3. Guardar en vera_configs.writing_style
        $this->saveProfile($userId, $profile);

        Log::info('✅ VeraWritingStyle: perfil guardado', [
            'user_id' => $userId,
            'tone'    => $profile['tone'],
            'formality_score' => $profile['formality_score'],
        ]);

        return [
            'profile'      => $profile,
            'emails_analyzed' => count($sentEmails),
        ];
    }

    /**
     * Obtiene los últimos N emails enviados del folder SENT de Gmail
     */
    protected function fetchSentEmailsFromGmail(EmailAccount $account, int $limit = 60): array
    {
        $client = $this->buildGoogleClient($account);
        $gmail  = new Gmail($client);

        // Listar mensajes del folder SENT
        $response = $gmail->users_messages->listUsersMessages('me', [
            'labelIds'   => 'SENT',
            'maxResults' => $limit,
        ]);

        $messages = $response->getMessages();
        if (empty($messages)) {
            return [];
        }

        $emails = [];
        foreach ($messages as $msg) {
            try {
                $fullMsg = $gmail->users_messages->get('me', $msg->getId(), [
                    'format' => 'full',
                ]);

                $headers = $this->parseHeaders($fullMsg->getPayload()->getHeaders());
                $body    = $this->extractBodyText($fullMsg->getPayload());

                if (empty(trim($body))) {
                    continue;
                }

                $emails[] = [
                    'subject' => $headers['subject'] ?? '',
                    'to'      => $headers['to'] ?? '',
                    'body'    => substr(strip_tags($body), 0, 800), // Max 800 chars por email
                ];
            } catch (\Exception $e) {
                Log::warning('VeraWritingStyle: error obteniendo mensaje', [
                    'msg_id' => $msg->getId(),
                    'error'  => $e->getMessage(),
                ]);
                continue;
            }
        }

        return $emails;
    }

    /**
     * Envía los emails a Gemini y extrae el perfil de escritura
     */
    protected function analyzeWithGemini(array $emails, int $userId): array
    {
        // Construir el bloque de emails para el prompt
        $emailsText = '';
        foreach (array_slice($emails, 0, 40) as $i => $email) {
            $emailsText .= "--- EMAIL " . ($i + 1) . " ---\n";
            $emailsText .= "Asunto: {$email['subject']}\n";
            $emailsText .= "Contenido:\n{$email['body']}\n\n";
        }

        $count = count($emails);

        $systemPrompt = <<<'SYSTEM'
Eres un experto en análisis de comunicación escrita.
Tu tarea es analizar emails enviados por una persona y extraer su perfil de escritura.
Devuelve ÚNICAMENTE un JSON válido, sin texto adicional, sin markdown.
SYSTEM;

        $userPrompt = "Analiza estos {$count} emails enviados por el usuario y extrae su perfil de escritura real.\n\n"
            . $emailsText
            . <<<'PROMPT'

Devuelve ÚNICAMENTE este JSON (sin texto adicional):
{
  "tone": "formal|professional|casual|friendly",
  "formality_score": 0.0,
  "avg_length": 200,
  "uses_emojis": false,
  "greetings": ["saludo1", "saludo2"],
  "closings": ["despedida1", "despedida2"],
  "language": "es",
  "common_expressions": ["expresión1"],
  "typical_sentence_length": "medium",
  "analysis_summary": "Descripción breve en español del estilo de escritura"
}

Reglas:
- tone: elige entre formal, professional, casual o friendly
- greetings: máximo 4, los más usados realmente (ej: "Hola,", "Buenos días,")
- closings: máximo 4, los más usados realmente (ej: "Saludos,", "Un abrazo,")
- formality_score: 0.0=muy casual, 0.5=profesional, 1.0=muy formal
- Devuelve valores numéricos reales, no rangos como "0.0-1.0"
- Si hay pocos emails, infiere a partir de los disponibles
PROMPT;

        $result = $this->ai->generateContent(
            $systemPrompt . "\n\n" . $userPrompt,
            ['userId' => $userId, 'actionType' => 'email_analysis']
        );

        $raw = $result['content'] ?? '{}';

        // Limpiar posibles bloques de código markdown
        $raw = preg_replace('/```json\s*/i', '', $raw);
        $raw = preg_replace('/```\s*/i', '', $raw);
        $raw = trim($raw);

        $profile = json_decode($raw, true);

        if (!$profile || !isset($profile['tone'])) {
            Log::warning('VeraWritingStyle: Gemini devolvió JSON inválido', ['raw' => substr($raw, 0, 500)]);
            // Perfil por defecto si falla el parse
            $profile = [
                'tone'           => 'professional',
                'formality_score' => 0.5,
                'avg_length'     => 200,
                'uses_emojis'    => false,
                'greetings'      => ['Hola,', 'Buenos días,'],
                'closings'       => ['Un saludo,', 'Saludos,'],
                'language'       => 'es',
            ];
        }

        return $profile;
    }

    /**
     * Guarda el perfil extraído en vera_configs.writing_style
     */
    protected function saveProfile(int $userId, array $profile): void
    {
        $config = VeraConfig::firstOrCreate(
            ['user_id' => $userId],
            ['writing_style' => []]
        );

        // Guardamos el perfil completo en writing_style
        $config->update([
            'writing_style' => [
                'tone'             => $profile['tone'] ?? 'professional',
                'formality_score'  => $profile['formality_score'] ?? 0.5,
                'avg_length'       => $profile['avg_length'] ?? 200,
                'uses_emojis'      => $profile['uses_emojis'] ?? false,
                'greetings'        => $profile['greetings'] ?? ['Hola,'],
                'closings'         => $profile['closings'] ?? ['Saludos,'],
                'language'         => $profile['language'] ?? 'es',
                'common_expressions'  => $profile['common_expressions'] ?? [],
                'typical_sentence_length' => $profile['typical_sentence_length'] ?? 'medium',
                'analysis_summary' => $profile['analysis_summary'] ?? '',
                'analyzed_at'      => now()->toISOString(),
            ],
        ]);
    }

    /**
     * Inicializa el Google Client con los tokens de la cuenta (igual que GmailSyncService)
     */
    protected function buildGoogleClient(EmailAccount $account): GoogleClient
    {
        $client = new GoogleClient();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));
        $client->addScope(Gmail::GMAIL_READONLY);

        $client->setAccessToken([
            'access_token'  => $account->access_token,
            'refresh_token' => $account->refresh_token,
            'expires_in'    => $account->token_expires_at
                ? $account->token_expires_at->diffInSeconds(now())
                : 3600,
        ]);

        if ($client->isAccessTokenExpired() && $account->refresh_token) {
            $newToken = $client->fetchAccessTokenWithRefreshToken($account->refresh_token);
            if (isset($newToken['access_token'])) {
                $account->update([
                    'access_token'    => $newToken['access_token'],
                    'token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                ]);
            }
        }

        return $client;
    }

    /**
     * Parsear headers del mensaje Gmail
     */
    protected function parseHeaders(array $headers): array
    {
        $parsed = [];
        foreach ($headers as $header) {
            $parsed[strtolower($header->getName())] = $header->getValue();
        }
        return $parsed;
    }

    /**
     * Extraer texto plano del payload (recursivo para multipart)
     */
    protected function extractBodyText($payload): string
    {
        if ($payload->getBody()->getData()) {
            $mimeType = $payload->getMimeType();
            if (str_contains($mimeType, 'text/plain') || str_contains($mimeType, 'text/html')) {
                return base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
            }
        }

        if ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                $text = $this->extractBodyText($part);
                if (!empty(trim($text))) {
                    return $text;
                }
            }
        }

        return '';
    }
}
