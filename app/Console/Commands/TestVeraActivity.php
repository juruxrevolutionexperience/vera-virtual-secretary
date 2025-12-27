<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Email;
use App\Models\VeraActivityLog;
use App\Services\VeraActivityService;

class TestVeraActivity extends Command
{
    protected $signature = 'test:vera-activity {--clear : Limpiar datos anteriores}';
    protected $description = 'Genera datos de prueba para el sistema de actividad de Vera';

    public function handle()
    {
        $this->info('🧪 Iniciando test de Vera Activity Log...');

        // Obtener usuario actual
        $user = User::first();
        if (!$user) {
            $this->error('❌ No hay usuarios en la base de datos');
            return 1;
        }

        // Limpiar datos anteriores si se especifica
        if ($this->option('clear')) {
            $this->info('🗑️  Limpiando datos anteriores...');
            VeraActivityLog::where('user_id', $user->id)->delete();
        }

        $service = new VeraActivityService();

        $this->info("👤 Generando actividades para: {$user->name}");
        $this->newLine();

        // 1. Crear algunos emails de prueba
        $this->info('📧 Creando emails de prueba...');

        $email1 = Email::create([
            'user_id' => $user->id,
            'from_email' => 'cliente@example.com',
            'from_name' => 'Juan Pérez',
            'subject' => 'Consulta sobre producto',
            'body_text' => 'Hola, me gustaría saber más sobre sus productos...',
            'received_at' => now()->subHours(2),
            'is_read' => false,
        ]);

        $email2 = Email::create([
            'user_id' => $user->id,
            'from_email' => 'newsletter@medium.com',
            'from_name' => 'Medium Daily Digest',
            'subject' => 'Your daily digest - 5 new articles',
            'body_text' => 'Here are today\'s top articles...',
            'received_at' => now()->subHours(5),
            'is_read' => false,
        ]);

        $email3 = Email::create([
            'user_id' => $user->id,
            'from_email' => 'equipo@empresa.com',
            'from_name' => 'María García',
            'subject' => 'Reunión de seguimiento proyecto',
            'body_text' => 'Hola! Necesitamos coordinar una reunión...',
            'received_at' => now()->subDay(),
            'is_read' => true,
        ]);

        // 2. Generar actividades de prueba
        $this->info('🎯 Generando actividades...');

        // Email procesado con alta confianza
        $activity1 = VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email1->id,
            'activity_type' => 'email_processed',
            'title' => "Email procesado de {$email1->from_email}",
            'description' => "Asunto: {$email1->subject}",
            'metadata' => [
                'from' => $email1->from_email,
                'subject' => $email1->subject,
            ],
            'tokens_consumed' => 1250,
            'cost' => 0.00125,
            'counts_towards_limit' => true,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => 0.95,
            'user_feedback' => 'approved',
            'created_at' => now()->subHours(2),
        ]);

        // Email respondido automáticamente
        $activity2 = VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email1->id,
            'activity_type' => 'email_replied',
            'title' => "Respuesta enviada a {$email1->from_email}",
            'description' => "Email de prospecto interesado. Requiere respuesta profesional con información del producto.",
            'metadata' => [
                'from' => $email1->from_email,
                'subject' => $email1->subject,
                'response_sent' => true,
                'response_preview' => 'Hola Juan, gracias por tu interés en nuestros productos...',
            ],
            'tokens_consumed' => 1850,
            'cost' => 0.00185,
            'counts_towards_limit' => true,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => 0.92,
            'user_feedback' => 'pending',
            'created_at' => now()->subHours(2),
        ]);

        // Newsletter archivado
        $activity3 = VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email2->id,
            'activity_type' => 'email_archived',
            'title' => "Email archivado de {$email2->from_email}",
            'description' => "Newsletter detectada automáticamente",
            'metadata' => [
                'from' => $email2->from_email,
                'subject' => $email2->subject,
                'reason' => 'Newsletter - archivado automáticamente',
            ],
            'tokens_consumed' => 850,
            'cost' => 0.00085,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => 0.88,
            'user_feedback' => 'pending',
            'created_at' => now()->subHours(5),
        ]);

        // Email categorizado
        $activity4 = VeraActivityLog::record([
            'user_id' => $user->id,
            'email_id' => $email3->id,
            'activity_type' => 'email_categorized',
            'title' => "Email categorizado como 'Trabajo - Interno'",
            'description' => "De {$email3->from_email}: {$email3->subject}",
            'metadata' => [
                'from' => $email3->from_email,
                'subject' => $email3->subject,
                'category' => 'Trabajo - Interno',
            ],
            'tokens_consumed' => 0,
            'cost' => 0,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'confidence_score' => 0.78,
            'user_feedback' => 'approved',
            'created_at' => now()->subDay(),
        ]);

        // Límite alcanzado (ejemplo)
        $activity5 = VeraActivityLog::record([
            'user_id' => $user->id,
            'activity_type' => 'limit_reached',
            'title' => "Límite mensual alcanzado",
            'description' => "Has alcanzado el límite de {$user->monthly_email_limit} emails de tu plan {$user->plan_type}",
            'metadata' => [
                'plan' => $user->plan_type,
                'limit' => $user->monthly_email_limit,
            ],
            'tokens_consumed' => 0,
            'cost' => 0,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'created_at' => now()->subDays(3),
        ]);

        // Interacción de chat
        $activity6 = VeraActivityLog::record([
            'user_id' => $user->id,
            'activity_type' => 'chat_interaction',
            'title' => "Chat con Vera",
            'description' => "Usuario: ¿Cuántos emails he procesado hoy?",
            'metadata' => [
                'message_preview' => '¿Cuántos emails he procesado hoy? Vera: Has procesado 12 emails hoy, 8 de ellos con IA...',
            ],
            'tokens_consumed' => 450,
            'cost' => 0.00045,
            'counts_towards_limit' => false,
            'plan_type_at_time' => $user->plan_type,
            'status' => 'completed',
            'created_at' => now()->subHours(1),
        ]);

        $this->newLine();
        $this->info('✅ Datos de prueba creados exitosamente!');
        $this->newLine();

        // Mostrar resumen
        $this->table(
            ['ID', 'Tipo', 'Título', 'Tokens', 'Confianza', 'Feedback'],
            [
                [$activity1->id, $activity1->activity_type, substr($activity1->title, 0, 30) . '...', $activity1->tokens_consumed, $activity1->confidence_score, $activity1->user_feedback],
                [$activity2->id, $activity2->activity_type, substr($activity2->title, 0, 30) . '...', $activity2->tokens_consumed, $activity2->confidence_score, $activity2->user_feedback],
                [$activity3->id, $activity3->activity_type, substr($activity3->title, 0, 30) . '...', $activity3->tokens_consumed, $activity3->confidence_score, $activity3->user_feedback],
                [$activity4->id, $activity4->activity_type, substr($activity4->title, 0, 30) . '...', $activity4->tokens_consumed, $activity4->confidence_score, $activity4->user_feedback],
                [$activity5->id, $activity5->activity_type, substr($activity5->title, 0, 30) . '...', $activity5->tokens_consumed, '-', $activity5->user_feedback],
                [$activity6->id, $activity6->activity_type, substr($activity6->title, 0, 30) . '...', $activity6->tokens_consumed, '-', $activity6->user_feedback],
            ]
        );

        $this->newLine();
        $this->info('🎯 Estadísticas generadas:');

        $stats = VeraActivityLog::getStats($user->id);
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de actividades', $stats['total_activities']],
                ['Tokens consumidos', number_format($stats['total_tokens'])],
                ['Costo total', '$' . number_format($stats['total_cost'], 6)],
                ['Confianza promedio', round($stats['avg_confidence'] * 100, 1) . '%'],
                ['Emails procesados', $stats['emails_processed']],
                ['Emails respondidos', $stats['emails_replied']],
                ['Tasa de aprobación', $stats['approval_rate'] . '%'],
            ]
        );

        $this->newLine();
        $this->info('🌐 Accede a la interfaz en:');
        $this->line('   👉 http://localhost/vera/activity');
        $this->newLine();

        $this->info('✨ Test completado!');

        return 0;
    }
}
