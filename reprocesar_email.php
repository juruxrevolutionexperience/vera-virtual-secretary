<?php
/**
 * Script para reprocesar un email específico con Vera
 * Uso: php reprocesar_email.php <email_id>
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Obtener email_id del argumento
$emailId = $argv[1] ?? null;

if (!$emailId) {
    echo "❌ Error: Debes proporcionar el ID del email\n";
    echo "Uso: php reprocesar_email.php <email_id>\n";
    exit(1);
}

echo "🔄 Reprocesando email ID: $emailId\n\n";

// Buscar el email
$email = App\Models\Email::find($emailId);

if (!$email) {
    echo "❌ Email no encontrado: $emailId\n";
    exit(1);
}

echo "📧 Email encontrado:\n";
echo "   De: {$email->from_email}\n";
echo "   Asunto: {$email->subject}\n";
echo "   Categoría: {$email->category}\n";
echo "   Procesado: " . ($email->vera_processed ? 'SÍ' : 'NO') . "\n\n";

// Resetear procesamiento
echo "🔄 Reseteando estado de procesamiento...\n";
$email->update([
    'vera_processed' => false,
    'vera_processed_at' => null,
]);

echo "✅ Email reseteado\n\n";

// Reprocesar con Vera
echo "🤖 Procesando con Vera (nueva lógica)...\n";
App\Jobs\ProcessEmailWithVera::dispatch($email);

echo "✅ Job de procesamiento encolado\n";
echo "\n📊 Revisa los logs en: storage/logs/laravel.log\n";
echo "   tail -f storage/logs/laravel.log | grep 'Vera\\|🤖\\|✅'\n\n";
