<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cancelar cita</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
            background:linear-gradient(135deg,#f8fafc,#eef2ff);min-height:100vh;display:flex;
            align-items:center;justify-content:center;padding:16px;color:#1e293b}
        .card{max-width:420px;width:100%;background:#fff;border-radius:18px;padding:32px;text-align:center;
            box-shadow:0 10px 40px rgba(2,6,23,.08);border:1px solid #e2e8f0}
        .ico{width:56px;height:56px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;
            justify-content:center;margin:0 auto 16px;font-size:26px;color:#94a3b8}
        h1{font-size:20px;font-weight:700;color:#1e293b}
        p{color:#64748b;font-size:14px;margin-top:8px}
        .foot{font-size:12px;color:#94a3b8;margin-top:24px}
    </style>
</head>
<body>
    @php
        $titulo = match($reason ?? '') {
            'expired' => 'Este enlace ha caducado',
            default   => 'Enlace no válido',
        };
        $sub = match($reason ?? '') {
            'expired' => 'El enlace para cancelar ha expirado. Escríbenos y te ayudamos.',
            default   => 'Este enlace de cancelación no existe o no es correcto.',
        };
    @endphp
    <div class="card">
        <div class="ico">!</div>
        <h1>{{ $titulo }}</h1>
        <p>{{ $sub }}</p>
        <p class="foot">Gestionado por Vera · Jurux</p>
    </div>
</body>
</html>
