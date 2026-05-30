<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva reserva pendiente</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:#18181b;padding:24px 32px;">
                            <p style="margin:0;font-size:13px;font-weight:600;color:#a1a1aa;letter-spacing:0.05em;text-transform:uppercase;">Vera · Nueva reserva</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 16px 32px;">
                            <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:600;color:#18181b;">Tienes una nueva solicitud de cita</h1>
                            <p style="margin:0;font-size:14px;color:#71717a;">Pendiente de confirmación</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f9fafb;border-radius:8px;padding:20px;">
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Cliente</span>
                                        <span style="font-size:15px;font-weight:600;color:#18181b;">{{ $meeting->contact_name ?? $req->contact_identifier }}</span>
                                    </td>
                                </tr>
                                @if($meeting->contact_email)
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Email</span>
                                        <span style="font-size:15px;color:#18181b;">{{ $meeting->contact_email }}</span>
                                    </td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Fecha y hora</span>
                                        <span style="font-size:15px;font-weight:600;color:#18181b;">{{ $dateLabel }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Duración</span>
                                        <span style="font-size:15px;color:#18181b;">{{ $meeting->duration_minutes }} minutos</span>
                                    </td>
                                </tr>
                                @if(!empty($req->context['booking_note']))
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Nota del cliente</span>
                                        <span style="font-size:15px;color:#18181b;">{{ $req->context['booking_note'] }}</span>
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 32px 32px 32px;">
                            <a href="{{ $panelUrl }}" style="display:inline-block;background:#18181b;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:15px;font-weight:600;">
                                Ver en el panel
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px;font-size:13px;color:#71717a;border-top:1px solid #f0f0f0;padding-top:24px;">
                            <p style="margin:0;">El hueco está provisionalmente bloqueado. Confirma o rechaza la cita desde tu panel de Vera.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0 0;font-size:12px;color:#a1a1aa;">Gestionado por Vera · Jurux</p>
            </td>
        </tr>
    </table>
</body>
</html>
