<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Invitación a la beta de Vera</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;color:#18181b;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background:#f4f4f5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                    <tr>
                        <td style="padding:32px 32px 16px 32px;">
                            <h1 style="margin:0 0 8px 0;font-size:22px;font-weight:600;color:#18181b;">Te han invitado a la beta de Vera</h1>
                            <p style="margin:0;font-size:14px;color:#71717a;">Acceso anticipado · plan {{ $invitation->plan_slug ?? 'estándar' }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px 32px;font-size:15px;line-height:1.6;color:#3f3f46;">
                            <p>Hola,</p>
                            <p>Vera es una secretaria virtual con IA que gestiona tu correo, tus reuniones y responde por ti con tu propio tono.</p>
                            <p>Esta invitación es personal y te da acceso anticipado a la versión beta. Tu opinión nos ayuda a ajustar Vera antes del lanzamiento público.</p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:0 32px 24px 32px;">
                            <a href="{{ $inviteUrl }}" style="display:inline-block;background:#18181b;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:8px;font-size:15px;font-weight:600;">
                                Aceptar invitación
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px;font-size:13px;line-height:1.6;color:#71717a;">
                            <p style="margin:0 0 8px 0;">Si el botón no funciona, copia este enlace en tu navegador:</p>
                            <p style="margin:0;word-break:break-all;color:#3f3f46;">{{ $inviteUrl }}</p>
                            <p style="margin:16px 0 0 0;">La invitación caduca el {{ $invitation->expires_at->format('d/m/Y') }}.</p>
                        </td>
                    </tr>
                </table>
                <p style="margin:24px 0 0 0;font-size:12px;color:#a1a1aa;">Recibiste este email porque alguien con acceso a Vera te invitó. Si no esperabas esta invitación, puedes ignorarla.</p>
            </td>
        </tr>
    </table>
</body>
</html>
