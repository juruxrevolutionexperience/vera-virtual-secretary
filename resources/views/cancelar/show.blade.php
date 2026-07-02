<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Cancelar cita · {{ $businessName }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
             background:#f5f5f5;color:#1a1a1a;min-height:100vh;-webkit-font-smoothing:antialiased}
        .page{max-width:480px;margin:0 auto;min-height:100vh;background:#fff;position:relative}

        /* ── Header ── */
        .hdr{padding:20px 20px 16px;border-bottom:1px solid #f0f0f0}
        .hdr h1{font-size:19px;font-weight:700;color:#1a1a1a}
        .hdr .sub{font-size:13px;color:#888;margin-top:3px}

        /* ── Lista de citas ── */
        .meeting-list{padding:0 20px}
        .meeting-row{display:flex;align-items:center;justify-content:space-between;
                     padding:18px 0;border-bottom:1px solid #f0f0f0;gap:12px}
        .meeting-row:last-child{border-bottom:none}
        .meeting-info{flex:1;min-width:0}
        .meeting-date{font-size:15px;font-weight:600;color:#1a1a1a;text-transform:capitalize;line-height:1.3}
        .meeting-service{font-size:13px;color:#888;margin-top:2px}
        .meeting-status{display:inline-flex;align-items:center;gap:4px;margin-top:4px;
                        font-size:12px;font-weight:500;padding:3px 8px;border-radius:20px}
        .status-confirmed{background:#dcfce7;color:#166534}
        .status-pending{background:#fef9c3;color:#713f12}

        /* ── Botón cancelar ── */
        .btn-cancel{flex-shrink:0;border:1.5px solid #fca5a5;background:#fff;color:#dc2626;
                    border-radius:20px;padding:9px 18px;font-size:13px;font-weight:600;
                    cursor:pointer;white-space:nowrap;transition:all .15s}
        .btn-cancel:hover:not(:disabled){background:#fef2f2;border-color:#ef4444}
        .btn-cancel:disabled{opacity:.4;cursor:default}

        /* ── Estado cancelado ── */
        .row-done{padding:18px 0;border-bottom:1px solid #f0f0f0}
        .row-done:last-child{border-bottom:none}
        .cancelled-badge{display:inline-flex;align-items:center;gap:6px;background:#f0f0f0;
                         border-radius:10px;padding:10px 14px;font-size:13px;color:#888}
        .cancelled-date{font-size:13px;color:#bbb;margin-top:4px;text-transform:capitalize}

        /* ── Sin citas ── */
        .empty-wrap{text-align:center;padding:48px 24px}
        .empty-icon{font-size:40px;margin-bottom:16px}
        .empty-title{font-size:17px;font-weight:600;color:#1a1a1a;margin-bottom:6px}
        .empty-sub{font-size:14px;color:#888;line-height:1.5}

        /* ── Modal confirmación ── */
        .overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:200;
                 display:flex;align-items:flex-end;justify-content:center}
        .modal{background:#fff;border-radius:20px 20px 0 0;padding:28px 24px;
               width:100%;max-width:480px;animation:slideUp .2s ease}
        @keyframes slideUp{from{transform:translateY(100%)}to{transform:translateY(0)}}
        .modal h2{font-size:18px;font-weight:700;margin-bottom:8px}
        .modal p{font-size:14px;color:#555;line-height:1.5;margin-bottom:24px}
        .modal-date{font-weight:600;color:#1a1a1a;text-transform:capitalize}
        .modal-actions{display:flex;gap:10px}
        .btn-back{flex:1;border:1.5px solid #e0e0e0;background:#fff;color:#555;
                  border-radius:24px;padding:13px;font-size:15px;font-weight:600;cursor:pointer;transition:.15s}
        .btn-back:hover{background:#f5f5f5}
        .btn-confirm-cancel{flex:1;border:none;background:#dc2626;color:#fff;
                            border-radius:24px;padding:13px;font-size:15px;font-weight:700;
                            cursor:pointer;transition:.15s}
        .btn-confirm-cancel:hover:not(:disabled){background:#b91c1c}
        .btn-confirm-cancel:disabled{opacity:.5;cursor:default}

        /* ── Footer ── */
        .foot{text-align:center;font-size:12px;color:#bbb;padding:20px 0}

        .hidden{display:none!important}
    </style>
</head>
<body>
<div class="page">

    <div class="hdr">
        <h1>Cancelar cita</h1>
        <p class="sub">{{ $businessName }} &middot; elige la cita que quieres cancelar</p>
    </div>

    <div class="meeting-list" id="lista">
        @forelse($meetings as $m)
            @php
                $label = $m->scheduled_at->copy()->setTimezone($tz)
                    ->locale('es')->translatedFormat('l d \d\e F \a \l\a\s H:i');
                $isPending = $m->status === 'pending';
            @endphp
            <div class="meeting-row" id="row-{{ $m->id }}">
                <div class="meeting-info">
                    <div class="meeting-date">{{ $label }}</div>
                    @if($m->service_name)
                    <div class="meeting-service">{{ $m->service_name }}</div>
                    @endif
                    <span class="meeting-status {{ $isPending ? 'status-pending' : 'status-confirmed' }}">
                        {{ $isPending ? 'Pendiente de confirmar' : 'Confirmada' }}
                    </span>
                </div>
                <button class="btn-cancel"
                        onclick="openModal({{ $m->id }}, '{{ addslashes($label) }}')"
                        id="btn-{{ $m->id }}">
                    Cancelar
                </button>
            </div>
        @empty
            <div class="empty-wrap">
                <div class="empty-icon">📅</div>
                <h2 class="empty-title">Sin citas pendientes</h2>
                <p class="empty-sub">No tienes citas proximas que cancelar.</p>
            </div>
        @endforelse
    </div>

    <p class="foot">Gestionado por Vera · Jurux</p>
</div>

<!-- Modal de confirmacion -->
<div class="overlay hidden" id="overlay" onclick="closeModal(event)">
    <div class="modal" onclick="event.stopPropagation()">
        <h2>Cancelar cita</h2>
        <p>Esta accion no se puede deshacer.<br>
           <span class="modal-date" id="modal-date"></span></p>
        <div class="modal-actions">
            <button class="btn-back" onclick="closeModalDirect()">Volver</button>
            <button class="btn-confirm-cancel" id="btn-modal-ok" onclick="confirmarCancelacion()">
                Si, cancelar
            </button>
        </div>
    </div>
</div>

<script>
const TOKEN = @json($token);
const CSRF  = document.querySelector('meta[name="csrf-token"]').content;

let activeMeetingId   = null;
let activeMeetingDate = null;

function openModal(id, date) {
    activeMeetingId   = id;
    activeMeetingDate = date;
    document.getElementById('modal-date').textContent = date;
    document.getElementById('overlay').classList.remove('hidden');
    document.getElementById('btn-modal-ok').disabled = false;
    document.getElementById('btn-modal-ok').textContent = 'Si, cancelar';
}

function closeModal(e) {
    if (e.target === document.getElementById('overlay')) closeModalDirect();
}

function closeModalDirect() {
    document.getElementById('overlay').classList.add('hidden');
    activeMeetingId = null;
}

async function confirmarCancelacion() {
    if (!activeMeetingId) return;
    const btnOk = document.getElementById('btn-modal-ok');
    btnOk.disabled = true;
    btnOk.textContent = 'Cancelando…';

    try {
        const res = await fetch('/cancelar/' + TOKEN, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
            body: JSON.stringify({meeting_id: activeMeetingId}),
        });
        const data = await res.json();

        if (res.ok && data.ok) {
            const row = document.getElementById('row-' + activeMeetingId);
            row.outerHTML =
                '<div class="row-done">' +
                  '<div class="cancelled-badge">✓ Cita cancelada</div>' +
                  '<div class="cancelled-date">' + activeMeetingDate + '</div>' +
                '</div>';
            closeModalDirect();
        } else {
            btnOk.disabled = false;
            btnOk.textContent = 'Si, cancelar';
            alert(data.error || 'No se pudo cancelar. Inténtalo de nuevo.');
        }
    } catch (e) {
        btnOk.disabled = false;
        btnOk.textContent = 'Si, cancelar';
        alert('Error de conexión. Inténtalo de nuevo.');
    }
}
</script>
</body>
</html>
