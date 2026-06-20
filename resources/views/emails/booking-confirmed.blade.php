<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $autoConfirmed ? 'Cita confirmada' : 'Solicitud recibida' }}</title>
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
                            @if($autoConfirmed)
                                <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:600;color:#18181b;">✅ Tu cita está confirmada</h1>
                                <p style="margin:0;font-size:14px;color:#71717a;">Te esperamos en el horario indicado.</p>
                            @else
                                <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:600;color:#18181b;">📅 Solicitud recibida</h1>
                                <p style="margin:0;font-size:14px;color:#71717a;">Tu solicitud está pendiente de confirmación por parte del negocio. Te avisaremos en cuanto la confirmen.</p>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px;">
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f9fafb;border-radius:8px;padding:20px;">
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Negocio</span>
                                        <span style="font-size:15px;font-weight:600;color:#18181b;">{{ $businessName }}</span>
                                    </td>
                                </tr>
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
                                @if(!empty($businessAddress) || !empty($mapsUrl))
                                <tr>
                                    <td style="padding:6px 0;">
                                        <span style="font-size:13px;color:#71717a;display:block;">Dónde</span>
                                        @if(!empty($businessAddress))<span style="font-size:15px;color:#18181b;">{{ $businessAddress }}</span>@endif
                                        @if(!empty($mapsUrl))
                                        <div style="margin-top:10px;">
                                            <a href="{{ $mapsUrl }}" style="display:inline-block;padding:9px 18px;background:#7c3aed;color:#ffffff;text-decoration:none;font-size:13px;font-weight:600;border-radius:6px;">📍 Cómo llegar</a>
                                        </div>
                                        @endif
                                    </td>
                                </tr>
                                @endif
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px;font-size:13px;color:#71717a;border-top:1px solid #f0f0f0;padding-top:24px;">
                            @if($autoConfirmed)
                                <p style="margin:0;">Si necesitas cambiar o cancelar la cita, responde a este email y te ayudamos.</p>
                            @else
                                <p style="margin:0;">Recibirás otro email cuando el negocio confirme tu cita. Si tienes dudas, responde a este email.</p>
                            @endif
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0 0;font-size:12px;color:#a1a1aa;">Gestionado por Vera · Jurux</p>
            </td>
        </tr>
    </table>
</body>
</html>
