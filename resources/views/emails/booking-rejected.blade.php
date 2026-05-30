<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Cita no disponible</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="background:#18181b;padding:24px 32px;">
                            <p style="margin:0;font-size:13px;font-weight:600;color:#a1a1aa;letter-spacing:0.05em;text-transform:uppercase;">{{ $businessName }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 16px 32px;">
                            <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:600;color:#18181b;">Lo sentimos, esa cita no está disponible</h1>
                            <p style="margin:0;font-size:14px;color:#71717a;">El negocio no ha podido confirmar tu solicitud para el horario elegido.</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#fef2f2;border-radius:8px;padding:20px;border-left:3px solid #ef4444;">
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Horario solicitado</span>
                                        <span style="font-size:15px;font-weight:600;color:#18181b;">{{ $dateLabel }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px;font-size:14px;line-height:1.6;color:#3f3f46;border-top:1px solid #f0f0f0;padding-top:24px;">
                            <p style="margin:0 0 8px 0;">Para elegir otro horario, responde a este email y te ayudamos a encontrar una nueva fecha.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0 0;font-size:12px;color:#a1a1aa;">Gestionado por Vera · Jurux</p>
            </td>
        </tr>
    </table>
</body>
</html>
