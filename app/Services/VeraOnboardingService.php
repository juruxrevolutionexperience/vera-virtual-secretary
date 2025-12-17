<?php

namespace App\Services;

/**
 * VeraOnboardingService
 *
 * Genera preguntas inteligentes basadas en el análisis de emails
 * para configurar Vera de la mejor manera posible.
 */
class VeraOnboardingService
{
    /**
     * Generar preguntas basadas en el análisis
     */
    public function generateQuestions(array $analysis): array
    {
        $questions = [];

        // Pregunta 1: SIEMPRE - Objetivo principal
        $questions[] = $this->getMainObjectiveQuestion();

        // Pregunta 2: Si hay emails enviados, preguntar sobre estilo
        if ($analysis['writing_style']['has_sent_emails']) {
            $questions[] = $this->getWritingStyleConfirmation($analysis['writing_style']);
        } else {
            $questions[] = $this->getWritingStyleSetup();
        }

        // Pregunta 3: Basada en tasa de respuesta
        $responseRate = $analysis['response_behavior']['response_rate'];
        if ($responseRate > 30) {
            $questions[] = $this->getAutoResponseQuestion($responseRate);
        } else {
            $questions[] = $this->getLowResponseExplanation($responseRate);
        }

        // Pregunta 4: Basada en tipos de emails
        $emailTypes = $analysis['email_types'];
        if ($emailTypes['percentages']['newsletters'] > 30) {
            $questions[] = $this->getNewsletterHandlingQuestion($emailTypes['percentages']['newsletters']);
        }

        // Pregunta 5: Contactos importantes
        $questions[] = $this->getImportantContactsQuestion($analysis['top_contacts']);

        // Pregunta 6: Horarios de disponibilidad
        $questions[] = $this->getAvailabilityQuestion($analysis['communication_patterns']);

        // Pregunta 7: Nivel de autonomía
        $questions[] = $this->getAutonomyLevelQuestion();

        return $questions;
    }

    /**
     * Procesar respuestas y generar configuración
     */
    public function processAnswers(array $answers, array $analysis): array
    {
        $config = [
            'main_objective' => $answers['main_objective'] ?? 'balanced',
            'writing_style' => $this->processWritingStyleAnswer($answers, $analysis),
            'auto_response' => $this->processAutoResponseAnswer($answers),
            'newsletter_handling' => $answers['newsletter_handling'] ?? 'auto_archive',
            'important_contacts' => $this->processImportantContacts($answers),
            'availability' => $this->processAvailability($answers),
            'autonomy_level' => $answers['autonomy_level'] ?? 'moderate',
            'confidence_threshold' => $this->calculateConfidenceThreshold($answers),
            're_analysis_days' => 30,
            'created_at' => now()->toDateTimeString(),
        ];

        return $config;
    }

    // ================ QUESTIONS ================

    protected function getMainObjectiveQuestion(): array
    {
        return [
            'id' => 'main_objective',
            'type' => 'choice',
            'question' => '¿Cuál es tu objetivo principal con Vera?',
            'description' => 'Esto determinará cómo Vera prioriza sus funciones.',
            'options' => [
                'filter' => [
                    'label' => 'Filtrar y organizar mi bandeja',
                    'description' => 'Reducir el ruido, archivar automáticamente newsletters y notificaciones'
                ],
                'respond' => [
                    'label' => 'Ayudarme a responder emails más rápido',
                    'description' => 'Generar borradores de respuesta automáticos'
                ],
                'balanced' => [
                    'label' => 'Ambos: filtrar Y responder',
                    'description' => 'Vera hará ambas cosas según el tipo de email (Recomendado)'
                ],
            ],
            'default' => 'balanced',
        ];
    }

    protected function getWritingStyleConfirmation(array $writingStyle): array
    {
        $tone = $writingStyle['tone'];
        $avgLength = $writingStyle['avg_email_length'];
        $usesEmojis = $writingStyle['uses_emojis'] ? 'Sí' : 'No';

        return [
            'id' => 'writing_style_confirmation',
            'type' => 'choice',
            'question' => "He detectado tu estilo de escritura como '{$tone}', emails de ~{$avgLength} caracteres. ¿Es correcto?",
            'description' => "Vera imitará este estilo al generar respuestas.",
            'context' => [
                'detected_tone' => $tone,
                'detected_length' => $avgLength,
                'detected_emojis' => $usesEmojis,
            ],
            'options' => [
                'yes' => [
                    'label' => 'Sí, ese es mi estilo',
                    'description' => 'Vera usará este estilo para generar respuestas'
                ],
                'adjust_more_formal' => [
                    'label' => 'Quiero ser más formal',
                    'description' => 'Vera ajustará para usar un tono más profesional'
                ],
                'adjust_more_casual' => [
                    'label' => 'Quiero ser más casual',
                    'description' => 'Vera ajustará para usar un tono más relajado'
                ],
                'manual' => [
                    'label' => 'Déjame configurarlo manualmente',
                    'description' => 'Te haré preguntas específicas sobre tu estilo'
                ],
            ],
            'default' => 'yes',
        ];
    }

    protected function getWritingStyleSetup(): array
    {
        return [
            'id' => 'writing_style_setup',
            'type' => 'choice',
            'question' => 'No tengo emails enviados tuyos. ¿Qué estilo prefieres para las respuestas?',
            'description' => 'Vera aprenderá de tus correcciones con el tiempo.',
            'options' => [
                'formal' => [
                    'label' => 'Formal y profesional',
                    'description' => 'Estimado/a, Cordialmente, lenguaje estructurado'
                ],
                'professional' => [
                    'label' => 'Profesional pero cercano',
                    'description' => 'Hola, Saludos, equilibrio entre profesional y amigable'
                ],
                'casual' => [
                    'label' => 'Casual y directo',
                    'description' => 'Hey, lenguaje relajado, puede usar emojis ocasionalmente'
                ],
            ],
            'default' => 'professional',
        ];
    }

    protected function getAutoResponseQuestion(float $responseRate): array
    {
        return [
            'id' => 'auto_response',
            'type' => 'choice',
            'question' => "Respondes al {$responseRate}% de emails. ¿Quieres que Vera genere borradores automáticamente?",
            'description' => 'Los borradores siempre requerirán tu aprobación antes de enviarse.',
            'options' => [
                'always' => [
                    'label' => 'Sí, para todos los emails que requieran respuesta',
                    'description' => 'Vera generará borradores para cualquier email que detecte necesita respuesta'
                ],
                'important_only' => [
                    'label' => 'Solo para emails importantes o urgentes',
                    'description' => 'Vera solo generará borradores para emails de alta prioridad (Recomendado)'
                ],
                'ask_first' => [
                    'label' => 'Preguntarme antes de generar cada borrador',
                    'description' => 'Vera te preguntará si quieres un borrador para cada email'
                ],
                'no' => [
                    'label' => 'No, prefiero responder manualmente',
                    'description' => 'Vera solo organizará tu bandeja, no generará respuestas'
                ],
            ],
            'default' => 'important_only',
        ];
    }

    protected function getLowResponseExplanation(float $responseRate): array
    {
        return [
            'id' => 'low_response_handling',
            'type' => 'choice',
            'question' => "Solo respondes al {$responseRate}% de emails. ¿Cómo quieres que Vera maneje esto?",
            'description' => 'La mayoría de tus emails no requieren respuesta.',
            'options' => [
                'filter_focus' => [
                    'label' => 'Enfocarse en filtrar y organizar',
                    'description' => 'Vera se centrará en limpiar tu bandeja y destacar lo importante (Recomendado)'
                ],
                'suggest_responses' => [
                    'label' => 'Sugerir respuestas solo cuando sea muy necesario',
                    'description' => 'Vera será muy selectiva, solo generará borradores para emails críticos'
                ],
            ],
            'default' => 'filter_focus',
        ];
    }

    protected function getNewsletterHandlingQuestion(float $percentage): array
    {
        return [
            'id' => 'newsletter_handling',
            'type' => 'choice',
            'question' => "{$percentage}% de tus emails son newsletters. ¿Cómo quieres manejarlas?",
            'description' => 'Las newsletters generan mucho ruido en tu bandeja.',
            'options' => [
                'auto_archive' => [
                    'label' => 'Archivar automáticamente',
                    'description' => 'Vera archivará newsletters automáticamente, las podrás buscar después (Recomendado)'
                ],
                'mark_read' => [
                    'label' => 'Marcar como leídas pero no archivar',
                    'description' => 'Quedarán en tu bandeja pero marcadas como leídas'
                ],
                'categorize' => [
                    'label' => 'Solo categorizarlas',
                    'description' => 'Vera las etiquetará pero no las moverá'
                ],
                'manual' => [
                    'label' => 'Déjamelas, yo decido',
                    'description' => 'Vera no tocará las newsletters'
                ],
            ],
            'default' => 'auto_archive',
        ];
    }

    protected function getImportantContactsQuestion(array $topContacts): array
    {
        $topSenders = array_slice($topContacts['top_senders'], 0, 3);
        $senderEmails = array_column($topSenders, 'email');

        return [
            'id' => 'important_contacts',
            'type' => 'multi_input',
            'question' => '¿Hay contactos que siempre debería priorizar?',
            'description' => 'Emails de estos contactos NUNCA se archivarán automáticamente.',
            'suggestions' => $senderEmails,
            'placeholder' => 'email@ejemplo.com (uno por línea)',
        ];
    }

    protected function getAvailabilityQuestion(array $patterns): array
    {
        $peakHour = $patterns['peak_hour'];
        $isBusinessHours = $patterns['is_mostly_business_hours'];

        $defaultStart = $isBusinessHours ? '09:00' : '10:00';
        $defaultEnd = $isBusinessHours ? '18:00' : '20:00';

        return [
            'id' => 'availability',
            'type' => 'time_range',
            'question' => '¿En qué horario prefieres que Vera genere y envíe respuestas?',
            'description' => "Detecté que tu hora pico es {$peakHour}. Las respuestas solo se enviarán en este horario.",
            'default_start' => $defaultStart,
            'default_end' => $defaultEnd,
            'options' => [
                'business_hours' => [
                    'label' => 'Horario laboral (9:00 - 18:00)',
                    'start' => '09:00',
                    'end' => '18:00'
                ],
                'extended' => [
                    'label' => 'Horario extendido (8:00 - 20:00)',
                    'start' => '08:00',
                    'end' => '20:00'
                ],
                'always' => [
                    'label' => 'Cualquier hora (24/7)',
                    'start' => '00:00',
                    'end' => '23:59'
                ],
                'custom' => [
                    'label' => 'Personalizado',
                    'custom' => true
                ],
            ],
            'default' => 'business_hours',
        ];
    }

    protected function getAutonomyLevelQuestion(): array
    {
        return [
            'id' => 'autonomy_level',
            'type' => 'choice',
            'question' => '¿Cuánta autonomía le das a Vera?',
            'description' => 'Esto define cuánto control tiene Vera sobre tus emails.',
            'options' => [
                'conservative' => [
                    'label' => 'Conservadora - Preguntarme todo',
                    'description' => 'Vera te pedirá confirmación para cada acción importante'
                ],
                'moderate' => [
                    'label' => 'Moderada - Balance entre autonomía y control',
                    'description' => 'Vera actuará automáticamente en casos claros, te preguntará en dudosos (Recomendado)'
                ],
                'aggressive' => [
                    'label' => 'Agresiva - Máxima autonomía',
                    'description' => 'Vera tomará decisiones automáticamente, solo te notificará'
                ],
            ],
            'default' => 'moderate',
        ];
    }

    // ================ ANSWER PROCESSING ================

    protected function processWritingStyleAnswer(array $answers, array $analysis): array
    {
        $styleAnswer = $answers['writing_style_confirmation'] ?? $answers['writing_style_setup'] ?? 'professional';

        if ($styleAnswer === 'yes') {
            // Usar el estilo detectado
            return [
                'tone' => $analysis['writing_style']['tone'],
                'formality_score' => $analysis['writing_style']['formality_score'],
                'avg_length' => $analysis['writing_style']['avg_email_length'],
                'uses_emojis' => $analysis['writing_style']['uses_emojis'],
                'greetings' => $analysis['writing_style']['top_greetings'],
                'closings' => $analysis['writing_style']['top_closings'],
            ];
        }

        // Mapear ajustes
        $adjustments = [
            'adjust_more_formal' => [
                'tone' => 'formal',
                'formality_score' => min(1.0, ($analysis['writing_style']['formality_score'] ?? 0.5) + 0.2),
                'uses_emojis' => false,
            ],
            'adjust_more_casual' => [
                'tone' => 'casual',
                'formality_score' => max(0.0, ($analysis['writing_style']['formality_score'] ?? 0.5) - 0.2),
                'uses_emojis' => true,
            ],
            'formal' => [
                'tone' => 'formal',
                'formality_score' => 0.9,
                'uses_emojis' => false,
                'greetings' => ['Estimado', 'Estimada'],
                'closings' => ['Cordialmente', 'Atentamente'],
            ],
            'professional' => [
                'tone' => 'profesional',
                'formality_score' => 0.6,
                'uses_emojis' => false,
                'greetings' => ['Hola', 'Buenos días'],
                'closings' => ['Saludos', 'Un saludo'],
            ],
            'casual' => [
                'tone' => 'casual',
                'formality_score' => 0.3,
                'uses_emojis' => true,
                'greetings' => ['Hola', 'Hey', 'Qué tal'],
                'closings' => ['Saludos', 'Un abrazo'],
            ],
        ];

        return array_merge([
            'avg_length' => $analysis['writing_style']['avg_email_length'] ?? 500,
        ], $adjustments[$styleAnswer] ?? $adjustments['professional']);
    }

    protected function processAutoResponseAnswer(array $answers): array
    {
        $autoResponse = $answers['auto_response'] ?? $answers['low_response_handling'] ?? 'important_only';

        return [
            'enabled' => !in_array($autoResponse, ['no', 'filter_focus']),
            'mode' => $autoResponse,
            'require_approval' => true, // SIEMPRE requerir aprobación
        ];
    }

    protected function processImportantContacts(array $answers): array
    {
        $contacts = $answers['important_contacts'] ?? '';

        if (is_string($contacts)) {
            $contacts = array_filter(array_map('trim', explode("\n", $contacts)));
        }

        return array_values($contacts);
    }

    protected function processAvailability(array $answers): array
    {
        $availability = $answers['availability'] ?? 'business_hours';

        $presets = [
            'business_hours' => ['start' => '09:00', 'end' => '18:00'],
            'extended' => ['start' => '08:00', 'end' => '20:00'],
            'always' => ['start' => '00:00', 'end' => '23:59'],
        ];

        if (isset($presets[$availability])) {
            return $presets[$availability];
        }

        // Si es custom, usar los valores proporcionados
        return [
            'start' => $answers['availability_start'] ?? '09:00',
            'end' => $answers['availability_end'] ?? '18:00',
        ];
    }

    protected function calculateConfidenceThreshold(array $answers): float
    {
        $autonomy = $answers['autonomy_level'] ?? 'moderate';

        return match($autonomy) {
            'conservative' => 0.90, // Muy selectiva
            'moderate' => 0.75,     // Balance
            'aggressive' => 0.60,   // Más permisiva
            default => 0.75,
        };
    }
}
