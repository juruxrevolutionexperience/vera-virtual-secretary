

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\VeraEmailAction;
use App\Services\GmailSyncService;

echo "🚀 Enviando respuesta de Vera...\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $action = VeraEmailAction::find(1);
    $email = $action->email;
    $account = $email->emailAccount;

    echo "📧 Detalles del envío:\n";
    echo "  De: " . $account->email_address . "\n";
    echo "  Para: " . $email->from_email . "\n";
    echo "  Asunto: Re: " . $email->subject . "\n\n";

    $service = new GmailSyncService();

    $subject = $email->subject;
    if (!str_starts_with(strtolower($subject), 're:')) {
        $subject = 'Re: ' . $subject;
    }

    echo "📤 Enviando email...\n";

    $sentEmail = $service->sendEmail($account, [
        'from' => $account->email_address,
        'to' => $email->from_email,
        'subject' => $subject,
        'body' => $action->generated_response,
    ]);

    if ($sentEmail) {
        $action->update([
            'response_sent' => true,
            'response_sent_at' => now(),
        ]);

        $email->update([
            'replied_at' => now(),
        ]);

        echo "\n✅ ¡EMAIL ENVIADO EXITOSAMENTE!\n";
        echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        echo "  Enviado a: " . $email->from_email . "\n";
        echo "  Fecha: " . now() . "\n";
        echo "  ID de acción: " . $action->id . "\n\n";
    } else {
        echo "\n❌ Error al enviar el email\n";
    }

} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
