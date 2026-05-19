<!-- Auto-logout por inactividad. Se inyecta automáticamente vía InjectIdleLogout middleware en cualquier respuesta HTML autenticada. -->
<div id="idle-logout-modal" role="dialog" aria-modal="true" aria-labelledby="idle-logout-title" hidden style="position:fixed;inset:0;z-index:2147483647;background:rgba(15,23,42,.6);display:flex;align-items:center;justify-content:center;font-family:'Inter',system-ui,-apple-system,sans-serif;">
    <div style="background:#fff;border-radius:14px;max-width:420px;width:92%;padding:28px 24px;box-shadow:0 25px 50px -12px rgba(0,0,0,.4);text-align:center;">
        <div style="width:56px;height:56px;border-radius:50%;background:#fef3c7;display:inline-flex;align-items:center;justify-content:center;margin-bottom:14px;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
        </div>
        <h2 id="idle-logout-title" style="font-size:18px;font-weight:700;color:#0f172a;margin:0 0 8px;">Tu sesión va a cerrarse</h2>
        <p style="font-size:14px;color:#64748b;margin:0 0 6px;line-height:1.5;">Por seguridad cerraremos tu sesión por inactividad en:</p>
        <p style="font-size:32px;font-weight:700;color:#7c3aed;margin:0 0 18px;font-variant-numeric:tabular-nums;"><span id="idle-logout-countdown">60</span>s</p>
        <div style="display:flex;gap:10px;justify-content:center;">
            <button type="button" id="idle-logout-stay" style="background:#7c3aed;color:#fff;border:0;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Sigo aquí</button>
            <button type="button" id="idle-logout-now" style="background:#fff;color:#475569;border:1px solid #cbd5e1;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;">Cerrar sesión</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';
    if (window.__idleLogoutLoaded) return;
    window.__idleLogoutLoaded = true;

    var IDLE_MS = 29 * 60 * 1000;
    var COUNTDOWN_S = 60;
    var THROTTLE_MS = 1000;

    var HEARTBEAT_URL = @json(route('auth.heartbeat'));
    var LOGOUT_URL = @json(route('auth.idle-logout'));
    var LOGIN_URL = @json(route('login'));

    var modal = document.getElementById('idle-logout-modal');
    var countdownEl = document.getElementById('idle-logout-countdown');
    var stayBtn = document.getElementById('idle-logout-stay');
    var nowBtn = document.getElementById('idle-logout-now');

    var idleTimer = null;
    var countdownTimer = null;
    var lastActivity = Date.now();
    var modalVisible = false;
    var loggingOut = false;

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.content : '';
    }

    function scheduleIdle() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showCountdown, IDLE_MS);
    }

    function showCountdown() {
        if (modalVisible) return;
        modalVisible = true;
        modal.hidden = false;
        var remaining = COUNTDOWN_S;
        countdownEl.textContent = remaining;
        countdownTimer = setInterval(function () {
            remaining -= 1;
            countdownEl.textContent = remaining;
            if (remaining <= 0) doLogout();
        }, 1000);
    }

    function hideCountdown() {
        clearInterval(countdownTimer);
        modal.hidden = true;
        modalVisible = false;
    }

    function onActivity() {
        if (modalVisible) return;
        var now = Date.now();
        if (now - lastActivity < THROTTLE_MS) return;
        lastActivity = now;
        scheduleIdle();
    }

    async function stay() {
        hideCountdown();
        lastActivity = Date.now();
        try {
            await fetch(HEARTBEAT_URL, {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin',
            });
        } catch (e) { /* no-op: si falla el heartbeat, el siguiente request normal refrescará */ }
        scheduleIdle();
    }

    async function doLogout() {
        if (loggingOut) return;
        loggingOut = true;
        clearInterval(countdownTimer);
        try {
            var resp = await fetch(LOGOUT_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken(),
                },
                credentials: 'same-origin',
            });
            var data = await resp.json().catch(function () { return {}; });
            window.location.href = data.redirect || LOGIN_URL;
        } catch (e) {
            window.location.href = LOGIN_URL;
        }
    }

    stayBtn.addEventListener('click', stay);
    nowBtn.addEventListener('click', doLogout);

    ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach(function (evt) {
        window.addEventListener(evt, onActivity, { passive: true });
    });

    scheduleIdle();
})();
</script>
