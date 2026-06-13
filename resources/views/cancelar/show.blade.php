<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cancelar cita · {{ $businessName }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
            background:linear-gradient(135deg,#f8fafc,#eef2ff);min-height:100vh;display:flex;
            align-items:flex-start;justify-content:center;padding:24px 16px;color:#1e293b}
        .card{max-width:480px;width:100%;background:#fff;border-radius:18px;padding:28px;
            box-shadow:0 10px 40px rgba(2,6,23,.08);border:1px solid #e2e8f0}
        h1{font-size:20px;font-weight:700;color:#1e293b;margin-bottom:4px}
        .sub{color:#64748b;font-size:14px;margin-bottom:20px}
        .cita{border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:12px;
            display:flex;align-items:center;justify-content:space-between;gap:12px;transition:.15s}
        .cita.activa{border-color:#c7d2fe;background:#f5f3ff}
        .cita .fecha{font-size:15px;font-weight:600;color:#1e293b;text-transform:capitalize}
        .cita .estado{font-size:12px;color:#94a3b8;margin-top:2px}
        .btn{border:0;border-radius:10px;padding:10px 16px;font-size:14px;font-weight:600;
            cursor:pointer;white-space:nowrap;transition:.15s}
        .btn-cancel{background:#fee2e2;color:#b91c1c}
        .btn-cancel:hover{background:#fecaca}
        .btn-cancel:disabled{opacity:.5;cursor:default}
        .vacio{text-align:center;color:#64748b;font-size:14px;padding:24px 0}
        .ok{background:#dcfce7;color:#166534;border-radius:10px;padding:10px 14px;font-size:13px;font-weight:600;margin-top:6px}
        .foot{font-size:12px;color:#94a3b8;margin-top:20px;text-align:center}
    </style>
</head>
<body>
    <div class="card">
        <h1>Cancelar tu cita</h1>
        <p class="sub">{{ $businessName }} · elige la cita que quieres cancelar</p>

        <div id="lista">
            @forelse($meetings as $m)
                @php
                    $label = $m->scheduled_at->copy()->setTimezone($tz)
                        ->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i');
                @endphp
                <div class="cita" data-id="{{ $m->id }}">
                    <div>
                        <div class="fecha">{{ $label }}</div>
                        <div class="estado">{{ $m->status === 'pending' ? 'Pendiente de confirmar' : 'Confirmada' }}</div>
                    </div>
                    <button class="btn btn-cancel" onclick="cancelar(this, {{ $m->id }})">Cancelar</button>
                </div>
            @empty
                <div class="vacio">No tienes citas próximas que cancelar.</div>
            @endforelse
        </div>

        <p class="foot">Gestionado por Vera · Jurux</p>
    </div>

    <script>
        const TOKEN = @json($token);
        const CSRF  = document.querySelector('meta[name="csrf-token"]').content;

        async function cancelar(btn, meetingId) {
            if (!confirm('¿Seguro que quieres cancelar esta cita?')) return;
            btn.disabled = true;
            btn.textContent = 'Cancelando…';
            try {
                const res = await fetch('/cancelar/' + TOKEN, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ meeting_id: meetingId }),
                });
                const data = await res.json();
                const row = btn.closest('.cita');
                if (res.ok && data.ok) {
                    row.innerHTML = '<div class="ok">✓ Cita cancelada</div>';
                } else {
                    btn.disabled = false;
                    btn.textContent = 'Cancelar';
                    alert(data.error || 'No se pudo cancelar. Inténtalo de nuevo.');
                }
            } catch (e) {
                btn.disabled = false;
                btn.textContent = 'Cancelar';
                alert('Error de conexión. Inténtalo de nuevo.');
            }
        }
    </script>
</body>
</html>
