const API = localStorage.getItem('API_URL') || `${location.origin.replace(/\/$/, '')}/api`;
const statusEl = document.getElementById('status');
let pingTimer = null;

function token() {
    return localStorage.getItem('token');
}

function setStatus(text, ok = true) {
    statusEl.textContent = text;
    statusEl.className = ok ? 'ok' : 'err';
}

async function api(path, options = {}) {
    const headers = Object.assign({ 'Accept': 'application/json', 'Content-Type': 'application/json' }, options.headers || {});
    if (token()) headers.Authorization = `Bearer ${token()}`;
    const res = await fetch(`${API}${path}`, Object.assign({}, options, { headers }));
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        throw new Error(data.message || data.email?.[0] || `HTTP ${res.status}`);
    }
    return data;
}

function showOps(on) {
    document.getElementById('login').hidden = on;
    document.getElementById('ops').hidden = !on;
}

async function loadSites() {
    const data = await api('/supervision/sites');
    const select = document.getElementById('site');
    select.innerHTML = (data.sites || []).map((s) => `<option value="${s.id}">${s.name}</option>`).join('');
}

function startPing() {
    stopPing();
    const send = async () => {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(async (pos) => {
            try {
                await api('/supervision/shifts/ping', {
                    method: 'POST',
                    body: JSON.stringify({
                        latitude: pos.coords.latitude,
                        longitude: pos.coords.longitude,
                        accuracy: pos.coords.accuracy,
                    }),
                });
            } catch (e) {
                setStatus(e.message, false);
            }
        });
    };
    send();
    pingTimer = setInterval(send, 30000);
}

function stopPing() {
    if (pingTimer) clearInterval(pingTimer);
    pingTimer = null;
}

document.getElementById('btn-login').onclick = async () => {
    try {
        const data = await api('/supervision/login', {
            method: 'POST',
            body: JSON.stringify({
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                device_name: 'supervision-pwa',
            }),
        });
        localStorage.setItem('token', data.token);
        showOps(true);
        await loadSites();
        setStatus(`Hola ${data.user.name}`);
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-open').onclick = async () => {
    try {
        await api('/supervision/shifts/open', { method: 'POST', body: '{}' });
        startPing();
        setStatus('Turno abierto. GPS cada 30 s.');
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-close').onclick = async () => {
    try {
        await api('/supervision/shifts/close', { method: 'POST', body: '{}' });
        stopPing();
        setStatus('Turno cerrado.');
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-review').onclick = async () => {
    try {
        const body = { client_id: Number(document.getElementById('site').value), notes: document.getElementById('notes').value };
        await api('/supervision/reviews', { method: 'POST', body: JSON.stringify(body) });
        setStatus('Revista registrada.');
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-logout').onclick = () => {
    stopPing();
    localStorage.removeItem('token');
    showOps(false);
    setStatus('Sesión cerrada.');
};

if (token()) {
    showOps(true);
    loadSites().catch((e) => setStatus(e.message, false));
}

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
}
