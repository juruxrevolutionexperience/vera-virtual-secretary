<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recordatorio de cita</title></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <tr><td style="background:#18181b;padding:20px 32px;">
                    <p style="margin:0;font-size:13px;font-weight:600;color:#a1a1aa;letter-spacing:0.05em;text-transform:uppercase;">{{ $businessName }}</p>
                </td></tr>
                <tr><td style="padding:28px 32px;">
                    <h1 style="margin:0 0 8px 0;font-size:21px;font-weight:600;color:#18181b;">
                        @if($kind === '1h')
                            ⏰ Tu cita es en 1 hora
                        @else
                            ⏰ Recordatorio de tu cita
                        @endif
                    </h1>
                    <p style="margin:0 0 20px 0;font-size:14px;color:#71717a;">
                        @if($kind === '1h')
                            Te esperamos muy pronto.
                        @else
                            Te recordamos que tienes una cita mañana.
                        @endif
                    </p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f9fafb;border-radius:8px;padding:20px;">
                        <tr><td style="padding:6px 0;">
                            <span style="font-size:13px;color:#71717a;display:block;">Fecha y hora</span>
                            <span style="font-size:15px;font-weight:600;color:#18181b;">{{ $dateLabel }}</span>
                        </td></tr>
                        <tr><td style="padding:6px 0;">
                            <span style="font-size:13px;color:#71717a;display:block;">Duración</span>
                            <span style="font-size:15px;color:#18181b;">{{ $meeting->duration_minutes }} minutos</span>
                        </td></tr>
                    </table>
                    <p style="margin:24px 0 0 0;font-size:13px;color:#71717a;border-top:1px solid #f0f0f0;padding-top:20px;">
                        Si necesitas cambiar o cancelar la cita, responde a este email y te ayudamos.
                    </p>
                </td></tr>
            </table>
            <p style="margin:20px 0 0 0;font-size:12px;color:#a1a1aa;">Gestionado por Vera · Jurux</p>
        </td></tr>
    </table>
</body>
</html>
