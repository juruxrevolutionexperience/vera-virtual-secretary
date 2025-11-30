<?php

namespace Database\Seeders;

use App\Models\Email;
use App\Models\EmailCategory;
use App\Models\User;
use App\Models\VeraEmailAction;
use Illuminate\Database\Seeder;

class EmailSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        if (!$user) {
            $this->command->warn('No hay usuarios en la base de datos.');
            return;
        }

        $inboxCategory = EmailCategory::where('slug', 'inbox')->first();
        $importantCategory = EmailCategory::where('slug', 'important')->first();
        $urgentProjectsCategory = EmailCategory::where('slug', 'urgent-projects')->first();

        $emails = [
            [
                'user_id' => $user->id,
                'email_category_id' => $urgentProjectsCategory?->id ?? $inboxCategory?->id,
                'from_email' => 'cliente@empresa.com',
                'from_name' => 'María González',
                'subject' => 'Propuesta Q4 2025 - Revisión Urgente',
                'body_text' => 'Hola, necesitamos revisar la propuesta para el cuarto trimestre. Es urgente que nos reunamos esta semana para definir el presupuesto y los entregables. Adjunto el documento preliminar.',
                'body_html' => '<p>Hola,</p><p>necesitamos revisar la propuesta para el cuarto trimestre. Es urgente que nos reunamos esta semana para definir el presupuesto y los entregables.</p><p>Adjunto el documento preliminar.</p>',
                'priority' => 'high',
                'is_read' => false,
                'has_attachments' => true,
                'attachments_count' => 1,
                'received_at' => now()->subMinutes(5),
                'vera_processed' => true,
                'vera_processed_at' => now()->subMinutes(4),
                'vera_summary' => 'Cliente solicita reunión urgente para revisar propuesta Q4 2025',
                'vera_tags' => ['urgente', 'reunión', 'propuesta'],
                'vera_sentiment_score' => 0.2,
                'vera_action_required' => 'schedule',
            ],
            [
                'user_id' => $user->id,
                'email_category_id' => $inboxCategory?->id,
                'from_email' => 'proveedor@tech.com',
                'from_name' => 'Carlos Ramírez',
                'subject' => 'Actualización de servicios - Nuevas características',
                'body_text' => 'Estimado cliente, nos complace informarte sobre las nuevas características disponibles en nuestro servicio premium. Incluyen análisis avanzados, reportes personalizados y soporte 24/7.',
                'body_html' => '<p>Estimado cliente,</p><p>Nos complace informarte sobre las nuevas características disponibles en nuestro servicio premium.</p>',
                'priority' => 'normal',
                'is_read' => false,
                'received_at' => now()->subMinutes(23),
                'vera_processed' => true,
                'vera_processed_at' => now()->subMinutes(22),
                'vera_summary' => 'Información sobre nuevas características del servicio',
                'vera_tags' => ['informativo', 'actualización'],
                'vera_sentiment_score' => 0.7,
                'vera_action_required' => 'none',
            ],
            [
                'user_id' => $user->id,
                'email_category_id' => $importantCategory?->id,
                'from_email' => 'ceo@startup.io',
                'from_name' => 'Ana Martínez',
                'subject' => 'Invitación: Reunión Junta Directiva',
                'body_text' => 'Estimados, los convoco a la reunión mensual de la junta directiva este jueves 15:00. Temas: Q4 results, 2026 roadmap, budget approval.',
                'body_html' => '<p>Estimados,</p><p>Los convoco a la reunión mensual de la junta directiva este jueves 15:00.</p><p><strong>Temas:</strong> Q4 results, 2026 roadmap, budget approval.</p>',
                'priority' => 'urgent',
                'is_read' => true,
                'is_starred' => true,
                'read_at' => now()->subHours(1),
                'received_at' => now()->subHours(2),
                'vera_processed' => true,
                'vera_processed_at' => now()->subHours(2),
                'vera_summary' => 'Convocatoria junta directiva - temas críticos Q4 y 2026',
                'vera_tags' => ['junta', 'reunión', 'estratégico'],
                'vera_sentiment_score' => 0.0,
                'vera_action_required' => 'reply',
            ],
            [
                'user_id' => $user->id,
                'email_category_id' => $inboxCategory?->id,
                'from_email' => 'newsletter@techcrunch.com',
                'from_name' => 'TechCrunch Newsletter',
                'subject' => 'Daily Digest: AI Trends 2025',
                'body_text' => 'Top stories today: OpenAI releases new model, Startup funding hits record high, AI regulation updates...',
                'body_html' => '<h2>Daily Digest</h2><p>Top stories today: OpenAI releases new model, Startup funding hits record high...</p>',
                'priority' => 'low',
                'is_read' => true,
                'read_at' => now()->subHours(3),
                'received_at' => now()->subHours(4),
                'vera_processed' => true,
                'vera_processed_at' => now()->subHours(4),
                'vera_summary' => 'Newsletter diario sobre tendencias tecnológicas',
                'vera_tags' => ['newsletter', 'tecnología', 'informativo'],
                'vera_sentiment_score' => 0.5,
                'vera_action_required' => 'archive',
            ],
            [
                'user_id' => $user->id,
                'email_category_id' => $inboxCategory?->id,
                'from_email' => 'hr@company.com',
                'from_name' => 'Recursos Humanos',
                'subject' => 'Recordatorio: Evaluación de Desempeño 2025',
                'body_text' => 'Estimado equipo, recordamos que el período de evaluaciones de desempeño cierra el próximo viernes. Por favor completen sus auto-evaluaciones.',
                'body_html' => '<p>Estimado equipo,</p><p>Recordamos que el período de evaluaciones de desempeño cierra el próximo viernes.</p>',
                'priority' => 'normal',
                'is_read' => false,
                'received_at' => now()->subDay(),
                'vera_processed' => true,
                'vera_processed_at' => now()->subDay(),
                'vera_summary' => 'Recordatorio para completar evaluaciones antes del viernes',
                'vera_tags' => ['rrhh', 'evaluación', 'recordatorio'],
                'vera_sentiment_score' => 0.1,
                'vera_action_required' => 'none',
            ],
        ];

        foreach ($emails as $emailData) {
            $email = Email::create($emailData);

            // Crear una acción de Vera para algunos emails
            if ($email->vera_processed && in_array($email->priority, ['high', 'urgent'])) {
                VeraEmailAction::create([
                    'email_id' => $email->id,
                    'user_id' => $user->id,
                    'action_type' => 'categorized',
                    'action_description' => 'Email categorizado automáticamente por prioridad',
                    'reasoning' => 'Detecté palabras clave como "urgente" y el remitente está en tu lista de contactos importantes.',
                    'confidence_score' => 0.92,
                ]);
            }
        }

        $this->command->info('✓ Emails de prueba creados exitosamente');
    }
}
