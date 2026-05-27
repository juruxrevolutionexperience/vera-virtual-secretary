<!DOCTYPE html>
<html lang="es" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reserva tu cita · {{ $businessName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body{font-family:'Inter',sans-serif}[x-cloak]{display:none}</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50 text-slate-800">
    <div class="max-w-xl mx-auto px-4 py-8 sm:py-14">

        <div class="bg-white rounded-2xl shadow-xl ring-1 ring-slate-200 overflow-hidden">
            {{-- Cabecera --}}
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-wider text-indigo-100">Reserva de cita</p>
                <h1 class="text-2xl font-bold mt-1">{{ $businessName }}</h1>
                <p class="text-sm text-indigo-100 mt-1">Elige el día y la hora que mejor te venga · {{ $duration }} min</p>
            </div>

            <div class="p-6">
                {{-- Paso 1: día --}}
                <div id="step-day">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">1. Elige un día</h2>
                    <div id="days" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
                </div>

                {{-- Paso 2: hora --}}
                <div id="step-time" class="mt-6 hidden">
                    <h2 class="text-sm font-semibold text-slate-700 mb-3">2. Elige la hora <span id="day-label" class="text-indigo-600"></span></h2>
                    <div id="slots" class="grid grid-cols-3 sm:grid-cols-4 gap-2"></div>
                    <p id="no-slots" class="hidden text-sm text-slate-500 py-4">No hay huecos libres ese día. Prueba con otro.</p>
                    <div id="slots-loading" class="hidden text-sm text-slate-400 py-4">Cargando huecos…</div>
                </div>

                {{-- Paso 3: confirmar --}}
                <div id="step-confirm" class="mt-6 hidden">
                    <h2 class="text-sm font-semibold text-slate-700 mb-2">3. Confirma tu cita</h2>
                    <div class="rounded-xl bg-indigo-50 ring-1 ring-indigo-100 p-4 mb-3">
                        <p class="text-sm text-slate-500">Cita seleccionada</p>
                        <p id="confirm-label" class="text-lg font-semibold text-indigo-700"></p>
                    </div>
                    <textarea id="note" rows="2" maxlength="500" placeholder="¿Algo que quieras añadir? (opcional)"
                        class="w-full rounded-lg border-slate-300 text-sm text-slate-900 placeholder-slate-400 focus:border-indigo-500 focus:ring-indigo-500 mb-3"></textarea>
                    <button id="confirm-btn"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 rounded-xl transition disabled:opacity-50">
                        Reservar cita
                    </button>
                    <button id="back-btn" class="w-full mt-2 text-sm text-slate-500 hover:text-slate-700 py-2">← Cambiar</button>
                </div>

                {{-- Éxito --}}
                <div id="step-done" class="hidden text-center py-6">
                    <div class="mx-auto w-14 h-14 rounded-full bg-green-100 flex items-center justify-center mb-4">
                        <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    </div>
                    <h2 id="done-title" class="text-xl font-bold text-slate-800"></h2>
                    <p id="done-sub" class="text-slate-500 mt-2 text-sm"></p>
                </div>

                <p id="error-msg" class="hidden mt-4 text-sm text-red-600 bg-red-50 rounded-lg p-3"></p>
            </div>
        </div>

        <p class="text-center text-xs text-slate-400 mt-5">Gestionado por Vera · Jurux</p>
    </div>

    <script>
    const TOKEN = @json($token);
    const CSRF  = document.querySelector('meta[name=csrf-token]').content;
    const slotsUrl = `/reservar/${TOKEN}/slots`;
    const storeUrl = `/reservar/${TOKEN}`;
    const DIAS = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    let selectedIso = null;

    const $ = id => document.getElementById(id);
    const show = (id, on=true) => $(id).classList.toggle('hidden', !on);
    const showError = msg => { const e=$('error-msg'); e.textContent=msg; e.classList.remove('hidden'); };
    const clearError = () => $('error-msg').classList.add('hidden');

    // Pinta los próximos 14 días como botones
    function renderDays() {
        const wrap = $('days');
        const today = new Date();
        for (let i = 0; i < 14; i++) {
            const d = new Date(today); d.setDate(today.getDate() + i);
            const iso = d.toISOString().slice(0, 10);
            const btn = document.createElement('button');
            btn.className = 'rounded-lg border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 py-2 px-1 text-center transition';
            btn.innerHTML = `<div class="text-[11px] text-slate-400">${DIAS[d.getDay()].slice(0,3)}</div>`
                          + `<div class="text-sm font-semibold text-slate-700">${d.getDate()} ${MESES[d.getMonth()]}</div>`;
            btn.onclick = () => pickDay(iso, d);
            wrap.appendChild(btn);
        }
    }

    async function pickDay(iso, dateObj) {
        clearError();
        $('day-label').textContent = `· ${DIAS[dateObj.getDay()]} ${dateObj.getDate()} ${MESES[dateObj.getMonth()]}`;
        show('step-time', true); show('step-confirm', false); show('step-done', false);
        $('slots').innerHTML = ''; show('no-slots', false); show('slots-loading', true);
        try {
            const r = await fetch(`${slotsUrl}?date=${iso}`, { headers: { 'Accept': 'application/json' } });
            const data = await r.json();
            show('slots-loading', false);
            const slots = data.slots || [];
            if (!slots.length) { show('no-slots', true); return; }
            slots.forEach(s => {
                const b = document.createElement('button');
                b.className = 'rounded-lg border border-slate-200 hover:border-indigo-500 hover:bg-indigo-600 hover:text-white py-2 text-sm font-medium text-slate-700 transition';
                b.textContent = s.time;
                b.onclick = () => pickSlot(s.iso, `${DIAS[dateObj.getDay()]} ${dateObj.getDate()} de ${MESES[dateObj.getMonth()]} a las ${s.time}`);
                $('slots').appendChild(b);
            });
        } catch (e) {
            show('slots-loading', false); showError('No se pudieron cargar los huecos. Recarga la página.');
        }
    }

    function pickSlot(iso, label) {
        selectedIso = iso;
        $('confirm-label').textContent = label;
        show('step-confirm', true);
        $('step-confirm').scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    $('back-btn').onclick = () => { show('step-confirm', false); selectedIso = null; };

    $('confirm-btn').onclick = async () => {
        if (!selectedIso) return;
        clearError();
        const btn = $('confirm-btn'); btn.disabled = true; btn.textContent = 'Reservando…';
        try {
            const r = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ datetime: selectedIso, note: $('note').value }),
            });
            const data = await r.json();
            if (!r.ok || !data.ok) { throw new Error(data.error || 'No se pudo reservar.'); }
            ['step-day','step-time','step-confirm'].forEach(s => show(s, false));
            const pending = data.status === 'pending';
            $('done-title').textContent = pending ? 'Reserva solicitada' : '¡Cita confirmada!';
            $('done-sub').textContent = pending
                ? `Tu cita para ${data.label} está pendiente de confirmación. Te avisaremos en cuanto se confirme.`
                : `Te esperamos el ${data.label}. Recibirás la invitación de calendario.`;
            show('step-done', true);
        } catch (e) {
            btn.disabled = false; btn.textContent = 'Reservar cita';
            showError(e.message || 'No se pudo reservar. Inténtalo de nuevo.');
        }
    };

    renderDays();
    </script>
</body>
</html>
