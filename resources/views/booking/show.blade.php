<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reserva · {{ $businessName }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;
             background:#f5f5f5;color:#1a1a1a;min-height:100vh;-webkit-font-smoothing:antialiased}

        /* ── Layout ── */
        .page{max-width:480px;margin:0 auto;min-height:100vh;background:#fff;position:relative;padding-bottom:80px}

        /* ── Header ── */
        .hdr{padding:20px 20px 16px;border-bottom:1px solid #f0f0f0}
        .hdr-back{background:none;border:none;cursor:pointer;padding:0;margin-bottom:12px;display:flex;align-items:center;gap:6px;font-size:14px;color:#666}
        .hdr-back:hover{color:#333}
        .hdr h1{font-size:19px;font-weight:700;color:#1a1a1a}
        .hdr .tz{font-size:12px;color:#999;margin-top:2px}

        /* ── Servicios ── */
        .svc-list{padding:0 20px}
        .svc-row{display:flex;align-items:center;justify-content:space-between;padding:18px 0;border-bottom:1px solid #f0f0f0;gap:12px}
        .svc-row:last-child{border-bottom:none}
        .svc-name{font-size:15px;font-weight:500;color:#1a1a1a;line-height:1.3}
        .svc-desc{font-size:13px;color:#888;margin-top:2px}
        .svc-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
        .svc-meta-box{text-align:right}
        .svc-price{font-size:15px;font-weight:700;color:#1a1a1a}
        .svc-dur{font-size:12px;color:#888;margin-top:1px}
        .btn-book{background:#4f46e5;color:#fff;border:none;border-radius:20px;padding:9px 20px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;transition:background .15s}
        .btn-book:hover{background:#4338ca}
        .no-svc-msg{padding:20px;font-size:14px;color:#888}

        /* ── Sección calendario ── */
        .cal-wrap{padding:0 20px}
        .svc-badge{display:flex;align-items:center;gap:8px;background:#f0f0f0;border-radius:20px;padding:8px 14px;margin:16px 0;font-size:13px;color:#333;width:fit-content}
        .svc-badge strong{font-weight:600}

        /* ── Calendario ── */
        .cal-title{font-size:17px;font-weight:700;color:#1a1a1a;margin-bottom:16px}
        .cal-nav{display:flex;align-items:center;gap:8px;margin-bottom:16px}
        .cal-nav .mlabel{font-size:16px;font-weight:700;color:#1a1a1a;flex:1;text-transform:capitalize}
        .cal-nav button{width:36px;height:36px;border:1px solid #e0e0e0;background:#fff;border-radius:50%;cursor:pointer;font-size:16px;color:#555;display:flex;align-items:center;justify-content:center;transition:all .15s}
        .cal-nav button:hover:not(:disabled){border-color:#4f46e5;color:#4f46e5}
        .cal-nav button:disabled{opacity:.3;cursor:default}
        .dow-row{display:grid;grid-template-columns:repeat(7,1fr);margin-bottom:8px}
        .dow-row span{text-align:center;font-size:12px;color:#aaa;font-weight:500;padding:4px 0}
        .calgrid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
        .day-cell{display:flex;flex-direction:column;align-items:center;gap:3px;padding:4px 2px}
        .day-btn{width:36px;height:36px;border:none;background:none;border-radius:50%;cursor:pointer;font-size:14px;font-weight:500;color:#1a1a1a;display:flex;align-items:center;justify-content:center;transition:all .15s;position:relative}
        .day-btn:hover:not(:disabled):not(.sel){background:#f0f0f0}
        .day-btn:disabled{color:#ccc;cursor:default}
        .day-btn.today{border:1.5px solid #4f46e5;color:#4f46e5}
        .day-btn.sel{background:#4f46e5;color:#fff}
        .day-btn.empty{visibility:hidden}
        .day-dot{width:5px;height:5px;border-radius:50%;background:#22c55e}
        .day-dot.hidden{visibility:hidden}

        /* ── Slots ── */
        .slots-title{font-size:15px;font-weight:600;color:#1a1a1a;margin:20px 0 12px}
        .slots-scroll{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;-webkit-overflow-scrolling:touch}
        .slots-scroll::-webkit-scrollbar{height:4px}
        .slots-scroll::-webkit-scrollbar-thumb{background:#e0e0e0;border-radius:2px}
        .slot-pill{flex-shrink:0;border:1.5px solid #e0e0e0;background:#fff;border-radius:20px;padding:8px 16px;font-size:14px;font-weight:500;color:#1a1a1a;cursor:pointer;white-space:nowrap;transition:all .15s}
        .slot-pill:hover:not(.busy){border-color:#4f46e5;color:#4f46e5}
        .slot-pill.sel{background:#4f46e5;border-color:#4f46e5;color:#fff}
        .slot-pill.busy{color:#ccc;border-color:#f0f0f0;cursor:default;text-decoration:line-through}
        .slots-empty{font-size:14px;color:#888;padding:8px 0}
        .slots-loading{font-size:14px;color:#888;padding:8px 0}

        /* ── Resumen ── */
        .summary-card{background:#f8f8f8;border-radius:14px;padding:16px;margin-top:20px}
        .summary-row{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:4px}
        .summary-svc{font-size:15px;font-weight:600;color:#1a1a1a}
        .summary-price{font-size:15px;font-weight:700;color:#1a1a1a}
        .summary-time{font-size:13px;color:#888;margin-top:2px}
        .summary-divider{height:1px;background:#ebebeb;margin:14px 0}
        .note-label{font-size:13px;font-weight:500;color:#555;margin-bottom:8px}
        .note-input{width:100%;border:1.5px solid #e0e0e0;border-radius:10px;padding:10px 12px;font:inherit;font-size:14px;color:#1a1a1a;resize:none;background:#fff;outline:none;transition:border .15s}
        .note-input:focus{border-color:#4f46e5}

        /* ── Barra sticky inferior ── */
        .sticky-bar{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #ebebeb;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;z-index:100;max-width:480px;margin:0 auto}
        .sticky-info{flex:1}
        .sticky-count{font-size:13px;color:#666}
        .sticky-price{font-size:20px;font-weight:700;color:#1a1a1a}
        .btn-continuar{background:#4f46e5;color:#fff;border:none;border-radius:24px;padding:13px 28px;font-size:15px;font-weight:700;cursor:pointer;white-space:nowrap;transition:background .15s;flex-shrink:0}
        .btn-continuar:hover:not(:disabled){background:#4338ca}
        .btn-continuar:disabled{opacity:.4;cursor:default}

        /* ── Confirmado ── */
        .done-wrap{text-align:center;padding:48px 24px}
        .done-icon{width:64px;height:64px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:30px}
        .done-title{font-size:22px;font-weight:700;margin-bottom:8px}
        .done-sub{font-size:15px;color:#666;line-height:1.5}

        /* ── Error ── */
        .err-bar{margin:16px 20px;padding:12px 14px;background:#fef2f2;border-radius:10px;font-size:14px;color:#dc2626}

        /* ── Footer ── */
        .foot{text-align:center;font-size:12px;color:#bbb;padding:16px 0 8px}

        .hidden{display:none!important}
    </style>
</head>
<body>
<div class="page">

    <!-- ── PANTALLA 0: Selección de servicio ───────────────── -->
    <div id="screen-service" {{ $services->isEmpty() ? 'class="hidden"' : '' }}>
        <div class="hdr">
            <h1>{{ $businessName }}</h1>
            <p class="tz">{{ $tzLabel }}</p>
        </div>
        <div class="svc-list">
            @forelse($services as $svc)
            <div class="svc-row">
                <div style="flex:1;min-width:0">
                    <div class="svc-name">{{ $svc->name }}</div>
                    @if($svc->description)
                    <div class="svc-desc">{{ $svc->description }}</div>
                    @endif
                </div>
                <div class="svc-right">
                    <div class="svc-meta-box">
                        @if($svc->price)<div class="svc-price">{{ $svc->price }}</div>@endif
                        <div class="svc-dur">{{ $svc->duration_minutes }} min</div>
                    </div>
                    <button class="btn-book" type="button"
                            onclick="pickService({{ $svc->id }}, '{{ addslashes($svc->name) }}', {{ $svc->duration_minutes }}, '{{ addslashes($svc->price ?? '') }}')">
                        Reservar
                    </button>
                </div>
            </div>
            @empty
            <p class="no-svc-msg">No hay servicios disponibles.</p>
            @endforelse
        </div>
        <p class="foot">Gestionado por Vera · Jurux</p>
    </div>

    <!-- ── PANTALLA 1: Calendario + hora ───────────────────── -->
    <div id="screen-cal" {{ $services->isNotEmpty() ? 'class="hidden"' : '' }}>
        <div class="hdr">
            <button class="hdr-back" type="button" id="btn-back-svc" onclick="backToServices()"
                    {{ $services->isEmpty() ? 'style="display:none"' : '' }}>
                ← {{ $businessName }}
            </button>
            <h1 id="cal-title-h1">Seleccionar fecha y hora</h1>
            <p class="tz">{{ $tzLabel }}</p>
        </div>

        <div class="cal-wrap">
            <!-- Badge servicio seleccionado -->
            <div id="svc-badge" class="svc-badge hidden">
                <span id="svc-badge-text"></span>
            </div>

            <!-- Calendario -->
            <div class="cal-nav">
                <span id="mlabel" class="mlabel"></span>
                <button id="prev-m" type="button">‹</button>
                <button id="next-m" type="button">›</button>
            </div>
            <div class="dow-row">
                <span>Lun</span><span>Mar</span><span>Mié</span>
                <span>Jue</span><span>Vie</span><span>Sáb</span><span>Dom</span>
            </div>
            <div id="calgrid" class="calgrid"></div>

            <!-- Slots -->
            <div id="slots-section" class="hidden">
                <p id="slots-title" class="slots-title"></p>
                <div id="slots" class="slots-scroll"></div>
                <p id="no-slots" class="slots-empty hidden">No hay huecos ese día. Prueba con otro.</p>
                <p id="slots-loading" class="slots-loading hidden">Cargando horas…</p>
            </div>

            <!-- Resumen (aparece al elegir hora) -->
            <div id="summary-card" class="summary-card hidden">
                <div class="summary-row">
                    <span id="sum-svc" class="summary-svc"></span>
                    <span id="sum-price" class="summary-price"></span>
                </div>
                <p id="sum-time" class="summary-time"></p>
                <div class="summary-divider"></div>
                <p class="note-label">¿Alguna nota o petición para tu visita?</p>
                <textarea id="note" class="note-input" rows="3" maxlength="500" placeholder="Nota de la reserva (opcional)"></textarea>
            </div>

        </div>

        <p id="error-msg" class="err-bar hidden"></p>
        <p class="foot">Gestionado por Vera · Jurux</p>
    </div>

    <!-- ── PANTALLA 2: Hecho ────────────────────────────────── -->
    <div id="screen-done" class="hidden">
        <div class="done-wrap">
            <div class="done-icon">✓</div>
            <h2 id="done-title" class="done-title"></h2>
            <p id="done-sub" class="done-sub"></p>
        </div>
        <p class="foot">Gestionado por Vera · Jurux</p>
    </div>

    <!-- ── Barra sticky inferior ────────────────────────────── -->
    <div id="sticky-bar" class="sticky-bar hidden">
        <div class="sticky-info">
            <p id="sticky-count" class="sticky-count"></p>
            <p id="sticky-price" class="sticky-price"></p>
        </div>
        <button id="btn-continuar" class="btn-continuar" type="button" disabled onclick="submitBooking()">
            Continuar
        </button>
    </div>

</div>

<script>
const TOKEN       = @json($token);
const CSRF        = document.querySelector('meta[name=csrf-token]').content;
const HAS_SVCS    = @json($services->isNotEmpty());
const ENABLED_DAYS = @json($enabledDays); // días de semana laborables [1=Lun..0=Dom]
const DIAS_ES     = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
const MESES_ES    = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
const MESES_CAP   = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

let selSvcId   = null;
let selSvcName = null;
let selSvcDur  = null;
let selSvcPrice = null;
let selIso     = null;
let selDayObj  = null;
let selTimeLabel = null;
let confirming = false;

const today   = new Date(); today.setHours(0,0,0,0);
const maxDate = new Date(today.getFullYear() + 1, today.getMonth(), today.getDate());
let viewY = today.getFullYear(), viewM = today.getMonth();

const $  = id => document.getElementById(id);
const show = (id, on=true) => $(id).classList.toggle('hidden', !on);
const showErr = m => { const e=$('error-msg'); e.textContent=m; e.classList.remove('hidden'); };
const clearErr = () => $('error-msg').classList.add('hidden');
const isoDate = d => d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
// JS getDay: 0=Dom,1=Lun,...,6=Sáb → convertir a nuestro mapa donde Lun=1..Dom=0
const jsToEnabled = jsDay => jsDay; // getDay() ya usa 0=Dom..6=Sáb

function pickService(id, name, dur, price) {
    selSvcId    = id;
    selSvcName  = name;
    selSvcDur   = dur;
    selSvcPrice = price;
    show('screen-service', false);
    show('screen-cal', true);
    $('svc-badge-text').textContent = name + ' · ' + dur + ' min' + (price ? ' · ' + price : '');
    show('svc-badge', true);
    updateStickyBar();
}

function backToServices() {
    if (!HAS_SVCS) return;
    selSvcId = selSvcName = selSvcDur = selSvcPrice = null;
    selIso = selDayObj = selTimeLabel = null;
    show('screen-service', true);
    show('screen-cal', false);
    show('sticky-bar', false);
    show('slots-section', false);
    show('summary-card', false);
    $('slots').innerHTML = '';
    document.querySelectorAll('.day-btn.sel').forEach(b => b.classList.remove('sel'));
}

function updateStickyBar() {
    const hasTime = !!selIso;
    if (!selSvcId && !selIso) { show('sticky-bar', false); return; }
    show('sticky-bar', true);
    $('sticky-count').textContent = '1 servicio · ' + (selSvcDur || '—') + ' min';
    $('sticky-price').textContent = selSvcPrice || '';
    $('btn-continuar').disabled = !hasTime;
}

/* ── Calendario ── */
function renderMonth() {
    $('mlabel').textContent = MESES_CAP[viewM] + ' ' + viewY;
    const startDow = (new Date(viewY, viewM, 1).getDay() + 6) % 7; // lunes = 0
    const dim = new Date(viewY, viewM+1, 0).getDate();
    const grid = $('calgrid'); grid.innerHTML = '';

    for (let i = 0; i < startDow; i++) {
        const cell = document.createElement('div'); cell.className = 'day-cell';
        const btn = document.createElement('button'); btn.className = 'day-btn empty'; btn.disabled = true;
        const dot = document.createElement('div'); dot.className = 'day-dot hidden';
        cell.appendChild(btn); cell.appendChild(dot); grid.appendChild(cell);
    }

    for (let d = 1; d <= dim; d++) {
        const date = new Date(viewY, viewM, d);
        const jsDay = date.getDay(); // 0=Dom..6=Sáb
        const cell = document.createElement('div'); cell.className = 'day-cell';
        const btn = document.createElement('button'); btn.type = 'button'; btn.className = 'day-btn'; btn.textContent = d;
        const dot = document.createElement('div'); dot.className = 'day-dot';

        const isPast = date < today;
        const isEnabled = ENABLED_DAYS.includes(jsDay);

        if (date.toDateString() === today.toDateString()) btn.classList.add('today');

        if (isPast || !isEnabled || date > maxDate) {
            btn.disabled = true;
            dot.classList.add('hidden');
        } else {
            btn.onclick = () => {
                document.querySelectorAll('.day-btn.sel').forEach(x => x.classList.remove('sel'));
                btn.classList.add('sel');
                selDayObj = date;
                loadSlots(isoDate(date), date, dot);
            };
        }

        cell.appendChild(btn); cell.appendChild(dot); grid.appendChild(cell);
    }

    $('prev-m').disabled = (viewY === today.getFullYear() && viewM === today.getMonth());
    $('next-m').disabled = (viewY === maxDate.getFullYear() && viewM === maxDate.getMonth());
}

$('prev-m').onclick = () => { if(viewM===0){viewM=11;viewY--;}else viewM--; renderMonth(); };
$('next-m').onclick = () => { if(viewM===11){viewM=0;viewY++;}else viewM++; renderMonth(); };

/* ── Slots ── */
async function loadSlots(isoD, dObj, dotEl) {
    clearErr();
    selIso = null; selTimeLabel = null;
    show('summary-card', false);
    updateStickyBar();

    const dayName = DIAS_ES[dObj.getDay()] + ' ' + dObj.getDate() + ' de ' + MESES_ES[dObj.getMonth()];
    $('slots-title').textContent = dayName.charAt(0).toUpperCase() + dayName.slice(1);
    show('slots-section', true);
    show('no-slots', false);
    show('slots-loading', true);
    $('slots').innerHTML = '';
    $('slots-section').scrollIntoView({behavior:'smooth', block:'nearest'});

    try {
        const svcParam = selSvcId ? '&service_id=' + selSvcId : '';
        const r = await fetch('/reservar/' + TOKEN + '/slots?date=' + isoD + svcParam, {headers:{'Accept':'application/json'}});
        const data = await r.json();
        show('slots-loading', false);
        const slots = data.slots || [];

        const freeSlots = slots.filter(s => s.free);
        if (!freeSlots.length) {
            if (dotEl) dotEl.classList.add('hidden');
            show('no-slots', slots.length === 0);
            if (slots.length > 0) show('no-slots', true);
            return;
        }

        slots.forEach(s => {
            if (!s.free) return; // solo mostramos horas libres (más limpio)
            const pill = document.createElement('button');
            pill.type = 'button';
            pill.className = 'slot-pill';
            pill.textContent = s.time;
            pill.onclick = () => selectSlot(s, dObj, pill);
            $('slots').appendChild(pill);
        });

    } catch(e) {
        show('slots-loading', false);
        showErr('No se pudieron cargar las horas. Recarga la página.');
    }
}

function selectSlot(s, dObj, pillEl) {
    document.querySelectorAll('.slot-pill.sel').forEach(p => p.classList.remove('sel'));
    pillEl.classList.add('sel');

    selIso = s.iso;
    const dur = selSvcDur || @json($duration);
    const [h, m] = s.time.split(':').map(Number);
    const endH = Math.floor((h * 60 + m + dur) / 60);
    const endM = (h * 60 + m + dur) % 60;
    const endStr = String(endH).padStart(2,'0') + ':' + String(endM).padStart(2,'0');

    selTimeLabel = s.time;

    // Rellenar resumen
    $('sum-svc').textContent  = selSvcName || 'Cita';
    $('sum-price').textContent = selSvcPrice || '';
    $('sum-time').textContent  = s.time + ' - ' + endStr;
    show('summary-card', true);
    $('summary-card').scrollIntoView({behavior:'smooth', block:'nearest'});

    updateStickyBar();
}

/* ── Enviar reserva ── */
async function submitBooking() {
    if (!selIso || confirming) return;
    confirming = true;
    clearErr();
    $('btn-continuar').disabled = true;
    $('btn-continuar').textContent = 'Reservando…';

    try {
        const body = {datetime: selIso, note: $('note').value};
        if (selSvcId) body.service_id = selSvcId;

        const r = await fetch('/reservar/' + TOKEN, {
            method: 'POST',
            headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
            body: JSON.stringify(body),
        });
        const data = await r.json();
        if (!r.ok || !data.ok) throw new Error(data.error || 'No se pudo reservar.');

        show('screen-cal', false);
        show('sticky-bar', false);
        const pending = data.status === 'pending';
        $('done-title').textContent = pending ? 'Reserva solicitada' : '¡Cita confirmada!';
        $('done-sub').textContent   = pending
            ? 'Tu cita para el ' + data.label + ' está pendiente de confirmación. Te avisaremos en cuanto se confirme.'
            : 'Te esperamos el ' + data.label + '. Recibirás la invitación de calendario.';
        show('screen-done', true);

    } catch(e) {
        confirming = false;
        $('btn-continuar').disabled = false;
        $('btn-continuar').textContent = 'Continuar';
        showErr(e.message || 'No se pudo reservar. Inténtalo de nuevo.');
    }
}

// Init
renderMonth();
if (!HAS_SVCS) {
    // Sin servicios: mostrar directamente el calendario
    show('screen-cal', true);
}
</script>
</body>
</html>
