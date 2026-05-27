<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Reserva tu cita · {{ $businessName }}</title>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
            background:linear-gradient(135deg,#f8fafc,#eef2ff);color:#1e293b;min-height:100vh;
            -webkit-font-smoothing:antialiased;line-height:1.5}
        .wrap{max-width:540px;margin:0 auto;padding:32px 16px}
        .card{background:#fff;border-radius:18px;box-shadow:0 10px 40px rgba(2,6,23,.08);
            border:1px solid #e2e8f0;overflow:hidden}
        .head{background:linear-gradient(90deg,#4f46e5,#7c3aed);padding:26px 24px;color:#fff}
        .head .tag{font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.08em;color:#c7d2fe}
        .head h1{font-size:24px;font-weight:700;margin:4px 0 2px}
        .head p{font-size:14px;color:#c7d2fe}
        .body{padding:24px}
        h2{font-size:14px;font-weight:600;color:#334155;margin-bottom:12px}
        .grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
        @media(min-width:480px){.grid{grid-template-columns:repeat(4,1fr)}}
        .opt{border:1px solid #e2e8f0;background:#fff;border-radius:10px;padding:9px 4px;text-align:center;
            cursor:pointer;transition:all .15s;font:inherit;color:#334155}
        .opt:hover{border-color:#6366f1;background:#eef2ff}
        .opt .dow{font-size:11px;color:#94a3b8}
        .opt .dnum{font-size:14px;font-weight:600;color:#334155}
        .slot{border:1px solid #e2e8f0;background:#fff;border-radius:10px;padding:10px 4px;text-align:center;
            cursor:pointer;transition:all .15s;font:inherit;font-size:14px;font-weight:500;color:#334155}
        .slot:hover{border-color:#4f46e5;background:#4f46e5;color:#fff}
        .sect{margin-top:24px}
        .hidden{display:none}
        .pick{border-radius:12px;background:#eef2ff;border:1px solid #e0e7ff;padding:16px;margin-bottom:12px}
        .pick .lbl{font-size:13px;color:#64748b}
        .pick .val{font-size:18px;font-weight:600;color:#4338ca}
        textarea{width:100%;border:1px solid #cbd5e1;border-radius:10px;padding:10px;font:inherit;font-size:14px;
            color:#0f172a;resize:vertical;margin-bottom:12px}
        textarea:focus{outline:none;border-color:#6366f1;box-shadow:0 0 0 3px rgba(99,102,241,.15)}
        .btn{width:100%;background:#4f46e5;color:#fff;font-weight:600;font-size:15px;padding:13px;border:none;
            border-radius:12px;cursor:pointer;transition:background .15s}
        .btn:hover{background:#4338ca}
        .btn:disabled{opacity:.5;cursor:default}
        .btn-link{width:100%;background:none;border:none;color:#64748b;font:inherit;font-size:13px;padding:8px;
            cursor:pointer;margin-top:6px}
        .muted{color:#64748b;font-size:14px;padding:16px 0}
        .err{margin-top:16px;font-size:14px;color:#dc2626;background:#fef2f2;border-radius:10px;padding:12px}
        .done{text-align:center;padding:24px 0}
        .check{width:56px;height:56px;border-radius:50%;background:#dcfce7;display:flex;align-items:center;
            justify-content:center;margin:0 auto 16px;font-size:28px;color:#16a34a}
        .done h2{font-size:20px;color:#1e293b;margin-bottom:8px}
        .done p{color:#64748b;font-size:14px}
        .foot{text-align:center;font-size:12px;color:#94a3b8;margin-top:20px}
    </style>
</head>
<body>
    <div class="wrap">
        <div class="card">
            <div class="head">
                <p class="tag">Reserva de cita</p>
                <h1>{{ $businessName }}</h1>
                <p>Elige el día y la hora que mejor te venga · {{ $duration }} min</p>
            </div>
            <div class="body">
                <div id="step-day">
                    <h2>1. Elige un día</h2>
                    <div id="days" class="grid"></div>
                </div>

                <div id="step-time" class="sect hidden">
                    <h2>2. Elige la hora <span id="day-label" style="color:#4f46e5"></span></h2>
                    <div id="slots" class="grid"></div>
                    <p id="no-slots" class="muted hidden">No hay huecos libres ese día. Prueba con otro.</p>
                    <p id="slots-loading" class="muted hidden">Cargando huecos…</p>
                </div>

                <div id="step-confirm" class="sect hidden">
                    <h2>3. Confirma tu cita</h2>
                    <div class="pick">
                        <p class="lbl">Cita seleccionada</p>
                        <p id="confirm-label" class="val"></p>
                    </div>
                    <textarea id="note" rows="2" maxlength="500" placeholder="¿Algo que quieras añadir? (opcional)"></textarea>
                    <button id="confirm-btn" class="btn">Reservar cita</button>
                    <button id="back-btn" class="btn-link">← Cambiar</button>
                </div>

                <div id="step-done" class="done hidden">
                    <div class="check">✓</div>
                    <h2 id="done-title"></h2>
                    <p id="done-sub"></p>
                </div>

                <p id="error-msg" class="err hidden"></p>
            </div>
        </div>
        <p class="foot">Gestionado por Vera · Jurux</p>
    </div>

    <script>
    const TOKEN = @json($token);
    const CSRF  = document.querySelector('meta[name=csrf-token]').content;
    const DIAS = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    let selectedIso = null;

    const $ = id => document.getElementById(id);
    const show = (id, on=true) => $(id).classList.toggle('hidden', !on);
    const showError = m => { const e=$('error-msg'); e.textContent=m; e.classList.remove('hidden'); };
    const clearError = () => $('error-msg').classList.add('hidden');

    function renderDays() {
        const today = new Date();
        for (let i=0;i<14;i++){
            const d=new Date(today); d.setDate(today.getDate()+i);
            const iso=d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0');
            const b=document.createElement('button');
            b.className='opt';
            b.innerHTML='<div class="dow">'+DIAS[d.getDay()].slice(0,3)+'</div><div class="dnum">'+d.getDate()+' '+MESES[d.getMonth()]+'</div>';
            b.onclick=()=>pickDay(iso,d);
            $('days').appendChild(b);
        }
    }

    async function pickDay(iso,dObj){
        clearError();
        $('day-label').textContent='· '+DIAS[dObj.getDay()]+' '+dObj.getDate()+' '+MESES[dObj.getMonth()];
        show('step-time',true); show('step-confirm',false); show('step-done',false);
        $('slots').innerHTML=''; show('no-slots',false); show('slots-loading',true);
        try{
            const r=await fetch('/reservar/'+TOKEN+'/slots?date='+iso,{headers:{'Accept':'application/json'}});
            const data=await r.json();
            show('slots-loading',false);
            const slots=data.slots||[];
            if(!slots.length){show('no-slots',true);return;}
            slots.forEach(s=>{
                const b=document.createElement('button');
                b.className='slot'; b.textContent=s.time;
                b.onclick=()=>pickSlot(s.iso, DIAS[dObj.getDay()]+' '+dObj.getDate()+' de '+MESES[dObj.getMonth()]+' a las '+s.time);
                $('slots').appendChild(b);
            });
        }catch(e){ show('slots-loading',false); showError('No se pudieron cargar los huecos. Recarga la página.'); }
    }

    function pickSlot(iso,label){
        selectedIso=iso; $('confirm-label').textContent=label;
        show('step-confirm',true);
        $('step-confirm').scrollIntoView({behavior:'smooth',block:'center'});
    }

    $('back-btn').onclick=()=>{ show('step-confirm',false); selectedIso=null; };

    $('confirm-btn').onclick=async()=>{
        if(!selectedIso)return;
        clearError();
        const btn=$('confirm-btn'); btn.disabled=true; btn.textContent='Reservando…';
        try{
            const r=await fetch('/reservar/'+TOKEN,{
                method:'POST',
                headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
                body:JSON.stringify({datetime:selectedIso,note:$('note').value}),
            });
            const data=await r.json();
            if(!r.ok||!data.ok){throw new Error(data.error||'No se pudo reservar.');}
            ['step-day','step-time','step-confirm'].forEach(s=>show(s,false));
            const pending=data.status==='pending';
            $('done-title').textContent=pending?'Reserva solicitada':'¡Cita confirmada!';
            $('done-sub').textContent=pending
                ?'Tu cita para '+data.label+' está pendiente de confirmación. Te avisaremos en cuanto se confirme.'
                :'Te esperamos el '+data.label+'. Recibirás la invitación de calendario.';
            show('step-done',true);
        }catch(e){
            btn.disabled=false; btn.textContent='Reservar cita';
            showError(e.message||'No se pudo reservar. Inténtalo de nuevo.');
        }
    };

    renderDays();
    </script>
</body>
</html>
