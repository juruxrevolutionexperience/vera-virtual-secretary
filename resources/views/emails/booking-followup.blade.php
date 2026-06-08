<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>¿Reservamos tu cita?</title></head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#1e293b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr><td align="center">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <tr><td style="background:#18181b;padding:20px 32px;">
                    <p style="margin:0;font-size:13px;font-weight:600;color:#a1a1aa;letter-spacing:0.05em;text-transform:uppercase;">{{ $businessName }}</p>
                </td></tr>
                <tr><td style="padding:28px 32px;">
                    <h1 style="margin:0 0 8px 0;font-size:21px;font-weight:600;color:#18181b;">¿Seguimos con tu cita?</h1>
                    <p style="margin:0 0 20px 0;font-size:15px;line-height:1.6;color:#3f3f46;">
                        Hace un par de días te pasamos nuestra agenda y aún no has reservado. Si te sigue interesando, elige el hueco que mejor te venga — es solo un clic.
                    </p>
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin:8px 0 4px 0;">
                        <tr><td style="border-radius:8px;background:#7c3aed;">
                            <a href="{{ $bookingUrl }}" style="display:inline-block;padding:13px 30px;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;border-radius:8px;">
                                Ver agenda y reservar
                            </a>
                        </td></tr>
                    </table>
                    <p style="margin:8px 0 0 0;font-size:12px;color:#9ca3af;">{{ $bookingUrl }}</p>
                    <p style="margin:24px 0 0 0;font-size:13px;color:#71717a;border-top:1px solid #f0f0f0;padding-top:20px;">
                        Si ya no te interesa o prefieres otra cosa, responde a este email y te ayudamos.
                    </p>
                </td></tr>
            </table>
            <p style="margin:20px 0 0 0;font-size:12px;color:#a1a1aa;">Gestionado por Vera · Jurux</p>
        </td></tr>
    </table>
</body>
</html>
