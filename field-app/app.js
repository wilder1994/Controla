const statusEl = document.getElementById('status');
let pingTimer = null;
let catalog = [];
let intake = null;
let currentModule = null;
let activity = null;
let lastGeo = null;
let streams = {};
let blobs = { odo: null, self: null, odoEnd: null, selfEnd: null };

function inferApi() {
    const host = location.hostname;
    if (/controla_supervision/i.test(host)) {
        return `${location.protocol}//${host.replace(/controla_supervision/i, 'controla')}/api`;
    }
    return `${location.origin.replace(/\/$/, '')}/api`;
}

function apiBase() {
    const stored = localStorage.getItem('API_URL');
    return (stored || inferApi()).replace(/\/$/, '');
}

function token() {
    return localStorage.getItem('token');
}

function setStatus(text, ok = true) {
    statusEl.textContent = text;
    statusEl.className = ok ? 'ok' : 'err';
}

function show(id) {
    ['login', 'open-shift', 'ops', 'close-shift'].forEach((key) => {
        document.getElementById(key).classList.toggle('hidden', key !== id);
    });
    if (id !== 'open-shift' && id !== 'close-shift') stopAllCams();
}

async function api(path, options = {}) {
    const headers = Object.assign({ Accept: 'application/json' }, options.headers || {});
    if (!(options.body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
    }
    if (token()) headers.Authorization = `Bearer ${token()}`;
    const res = await fetch(`${apiBase()}${path}`, Object.assign({}, options, { headers }));
    const data = await res.json().catch(() => ({}));
    if (!res.ok) {
        const first = data.errors ? Object.values(data.errors)[0] : null;
        throw new Error((first && first[0]) || data.message || data.email?.[0] || `HTTP ${res.status}`);
    }
    return data;
}

function siteId() {
    const value = document.getElementById('site').value;
    return value ? Number(value) : null;
}

async function geo() {
    return new Promise((resolve) => {
        if (!navigator.geolocation) return resolve(lastGeo);
        navigator.geolocation.getCurrentPosition(
            (pos) => {
                lastGeo = { latitude: pos.coords.latitude, longitude: pos.coords.longitude, accuracy: pos.coords.accuracy };
                resolve(lastGeo);
            },
            () => resolve(lastGeo),
            { enableHighAccuracy: true, timeout: 4000, maximumAge: 15000 },
        );
    });
}

function checksHtml(items, prefix) {
    return items.map((item) => `
        <label class="check">
            <input type="checkbox" data-check="${prefix}" data-key="${item.key}">
            <span>${item.label}</span>
        </label>`).join('');
}

function readChecks(prefix) {
    const out = {};
    document.querySelectorAll(`input[data-check="${prefix}"]`).forEach((el) => {
        out[el.dataset.key] = el.checked;
    });
    return out;
}

async function startCam(videoId, facing) {
    stopCam(videoId);
    const video = document.getElementById(videoId);
    if (!navigator.mediaDevices?.getUserMedia) {
        setStatus('Este navegador no permite cámara. Use HTTPS o Chrome en el celular.', false);
        return;
    }
    try {
        const stream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: facing },
            audio: false,
        });
        streams[videoId] = stream;
        video.srcObject = stream;
        await video.play();
    } catch (e) {
        setStatus('Permita la cámara. En local, Chrome puede exigir HTTPS.', false);
    }
}

function stopCam(videoId) {
    const stream = streams[videoId];
    if (stream) stream.getTracks().forEach((t) => t.stop());
    delete streams[videoId];
}

function stopAllCams() {
    Object.keys(streams).forEach(stopCam);
}

function snapTo(videoId, imgId, key) {
    const video = document.getElementById(videoId);
    if (!video.videoWidth) {
        setStatus('Active la cámara antes de tomar la foto.', false);
        return;
    }
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);
    canvas.toBlob((blob) => {
        blobs[key] = blob;
        const img = document.getElementById(imgId);
        img.src = URL.createObjectURL(blob);
        img.classList.remove('hidden');
        setStatus('Foto tomada.');
    }, 'image/jpeg', 0.86);
}

function bindCameras() {
    document.getElementById('btn-cam-odo').onclick = () => {
        document.getElementById('snap-odo').classList.add('hidden');
        blobs.odo = null;
        startCam('cam-odo', { exact: 'environment' }).catch(() => startCam('cam-odo', 'environment'));
    };
    document.getElementById('btn-snap-odo').onclick = () => snapTo('cam-odo', 'snap-odo', 'odo');
    document.getElementById('btn-cam-self').onclick = () => {
        document.getElementById('snap-self').classList.add('hidden');
        blobs.self = null;
        startCam('cam-self', 'user');
    };
    document.getElementById('btn-snap-self').onclick = () => snapTo('cam-self', 'snap-self', 'self');
    document.getElementById('btn-cam-odo-end').onclick = () => {
        document.getElementById('snap-odo-end').classList.add('hidden');
        blobs.odoEnd = null;
        startCam('cam-odo-end', 'environment');
    };
    document.getElementById('btn-snap-odo-end').onclick = () => snapTo('cam-odo-end', 'snap-odo-end', 'odoEnd');
    document.getElementById('btn-cam-self-end').onclick = () => {
        document.getElementById('snap-self-end').classList.add('hidden');
        blobs.selfEnd = null;
        startCam('cam-self-end', 'user');
    };
    document.getElementById('btn-snap-self-end').onclick = () => snapTo('cam-self-end', 'snap-self-end', 'selfEnd');
}

function renderIntake() {
    const slot = document.getElementById('shift-template');
    const templates = intake.shift_templates || [];
    slot.innerHTML = templates.map((s) => `<option value="${s.id}" data-schedule="${s.schedule}">${s.name}</option>`).join('')
        || '<option value="">Sin turnos activos</option>';
    document.getElementById('shift-schedule').value = templates[0]?.schedule || '';
    slot.onchange = () => {
        const opt = slot.selectedOptions[0];
        document.getElementById('shift-schedule').value = opt?.dataset.schedule || '';
    };
    const zones = intake.zones || [];
    document.getElementById('route-zone').innerHTML = zones.map((z) => `<option value="${z.id}">${z.name}</option>`).join('')
        || '<option value="">Sin zonas activas</option>';
    document.getElementById('ppe-list').innerHTML = checksHtml(intake.ppe || [], 'ppe');
    document.getElementById('vehicle-check-list').innerHTML = checksHtml(intake.vehicle_check || [], 'vcheck');
    const ppeToggle = document.getElementById('ppe-toggle');
    if (ppeToggle) ppeToggle.querySelector('span').textContent = `2. Preoperacional · EPP (${(intake.ppe || []).length})`;
    const vToggle = document.getElementById('vcheck-toggle');
    if (vToggle) vToggle.querySelector('span').textContent = `Preoperacional vehículo (${(intake.vehicle_check || []).length})`;

    const select = document.getElementById('fleet-vehicle');
    const vehicles = intake.vehicles || [];
    const first = intake.first_vehicle || vehicles.length === 0;
    document.getElementById('vehicle-hint').textContent = first
        ? 'Primera vez: diligencie la ficha completa del vehículo de flota.'
        : 'Elija un vehículo o registre uno nuevo.';
    select.innerHTML = first
        ? '<option value="">Registrar vehículo nuevo</option>'
        : ['<option value="">Registrar vehículo nuevo</option>']
            .concat(vehicles.map((v) => `<option value="${v.id}" data-km="${v.last_km}">${v.label} · ${v.last_km} km</option>`))
            .join('');
    document.getElementById('vehicle-form').innerHTML = `
        <label>Placa</label><input id="v-plate" maxlength="12">
        <label>Marca</label><input id="v-brand">
        <div class="row">
            <div><label>Línea</label><input id="v-line"></div>
            <div><label>Modelo</label><input id="v-model"></div>
        </div>
        <div class="row">
            <div><label>SOAT</label><input id="v-soat" type="date"></div>
            <div><label>Tecnomecánica</label><input id="v-tm" type="date"></div>
        </div>`;
    const form = document.getElementById('vehicle-form');
    const applyVehicle = () => {
        const id = select.value;
        const found = vehicles.find((v) => String(v.id) === id);
        form.classList.toggle('hidden', Boolean(id));
        document.getElementById('last-km').textContent = found ? `Último cierre: ${found.last_km} km` : '';
        if (found) document.getElementById('km-start').value = found.last_km;
    };
    select.onchange = applyVehicle;
    applyVehicle();
}

function renderHub() {
    const logs = activity?.logs || {};
    document.getElementById('mod-grid').innerHTML = catalog.map((mod) => {
        const count = mod.key === 'reviews' ? (activity?.reviews || 0) : (logs[mod.key] || 0);
        return `<button type="button" class="mod" data-key="${mod.key}">
            <b>${mod.label}</b>
            <span>${mod.hint}</span>
            <span class="n">${count} en este turno</span>
        </button>`;
    }).join('');
    document.getElementById('mod-grid').querySelectorAll('.mod').forEach((btn) => {
        btn.onclick = () => openModule(btn.dataset.key);
    });
}

function fieldControl(field) {
    const req = field.required ? 'required' : '';
    if (field.type === 'textarea') return `<textarea id="f-${field.name}" ${req}></textarea>`;
    if (field.type === 'select') {
        const opts = (field.options || []).map((o) => `<option value="${o.value}">${o.label}</option>`).join('');
        return `<select id="f-${field.name}" ${req}>${opts}</select>`;
    }
    if (field.type === 'radio') {
        return `<div class="opts">${(field.options || []).map((o, i) => `
            <label class="opt"><input type="radio" name="f-${field.name}" value="${o.value}" ${i === 0 && field.required ? 'checked' : ''}><span>${o.label}</span></label>`).join('')}</div>`;
    }
    if (field.type === 'checkbox') return `<label class="opt"><input id="f-${field.name}" type="checkbox"><span>${field.label}</span></label>`;
    if (field.type === 'number') return `<input id="f-${field.name}" type="number" min="${field.min ?? 1}" value="${field.min ?? 1}" ${req}>`;
    if (field.type === 'date') return `<input id="f-${field.name}" type="date" ${req}>`;
    return `<input id="f-${field.name}" type="text" ${req}>`;
}

function openModule(key) {
    currentModule = catalog.find((m) => m.key === key);
    document.getElementById('module-card').classList.remove('hidden');
    document.getElementById('module-title').textContent = currentModule.label;
    document.getElementById('module-hint').textContent = currentModule.hint;
    document.getElementById('module-form').innerHTML = (currentModule.fields || []).map((field) => (
        field.type === 'checkbox' ? fieldControl(field) : `<label>${field.label}</label>${fieldControl(field)}`
    )).join('');
    document.getElementById('module-card').scrollIntoView({ behavior: 'smooth' });
}

function readPayload() {
    const payload = {};
    (currentModule?.fields || []).forEach((field) => {
        if (field.type === 'radio') {
            const picked = document.querySelector(`input[name="f-${field.name}"]:checked`);
            payload[field.name] = picked ? picked.value : null;
            return;
        }
        const el = document.getElementById(`f-${field.name}`);
        if (!el) return;
        if (field.type === 'checkbox') payload[field.name] = el.checked;
        else if (field.type === 'number') payload[field.name] = el.value === '' ? null : Number(el.value);
        else payload[field.name] = el.value === '' ? null : el.value;
    });
    return payload;
}

async function loadSites() {
    const data = await api('/supervision/sites');
    const select = document.getElementById('site');
    select.innerHTML = (data.sites || []).map((s) => `<option value="${s.id}">${s.name}</option>`).join('')
        || '<option value="">Sin sitios con Supervisión</option>';
}

async function loadCatalog() {
    const data = await api('/supervision/catalog');
    catalog = data.modules || [];
    renderHub();
}

async function loadIntake() {
    intake = await api('/supervision/intake');
    renderIntake();
}

async function loadCurrent() {
    const data = await api('/supervision/shifts/current');
    activity = data.activity;
    const shift = data.shift;
    if (!shift) {
        document.getElementById('shift-label').textContent = 'Sin turno';
        stopPing();
        return null;
    }
    document.getElementById('shift-label').textContent = 'Turno abierto';
    document.getElementById('shift-meta').textContent = [
        shift.schedule_label,
        shift.route_zone,
        shift.fleet_vehicle?.plate,
        shift.km_start != null ? `${shift.km_start} km` : null,
    ].filter(Boolean).join(' · ');
    document.getElementById('activity').textContent =
        `${activity?.reviews || 0} revistas · ${Object.values(activity?.logs || {}).reduce((a, b) => a + Number(b || 0), 0)} registros`;
    document.getElementById('km-end').value = shift.km_start || '';
    renderHub();
    startPing();
    return shift;
}

async function loadRecs() {
    const root = document.getElementById('recs');
    const id = siteId();
    if (!id) {
        root.innerHTML = '<p class="hint">Seleccione un sitio.</p>';
        return;
    }
    const data = await api(`/supervision/recommendations?client_id=${id}`);
    const rows = data.recommendations || [];
    if (!rows.length) {
        root.innerHTML = '<p class="hint">Sin recomendaciones abiertas.</p>';
        return;
    }
    root.innerHTML = rows.map((rec) => `
        <div class="rec" data-id="${rec.id}">
            <strong>${rec.title}</strong>
            <div class="meta">${rec.priority} · ${rec.status}${rec.due_date ? ` · ${rec.due_date}` : ''}</div>
            <p class="hint">${rec.body || ''}</p>
            <div class="row">
                ${rec.status === 'open' ? '<button type="button" class="secondary" data-next="progress">En proceso</button>' : ''}
                ${rec.status !== 'closed' ? '<button type="button" data-next="closed">Cerrar</button>' : ''}
            </div>
        </div>`).join('');
    root.querySelectorAll('button[data-next]').forEach((btn) => {
        btn.onclick = () => advanceRec(Number(btn.closest('.rec').dataset.id), btn.dataset.next);
    });
}

async function advanceRec(id, status) {
    try {
        const pos = await geo();
        await api(`/supervision/recommendations/${id}`, {
            method: 'PATCH',
            body: JSON.stringify({ status, latitude: pos?.latitude, longitude: pos?.longitude }),
        });
        setStatus(status === 'closed' ? 'Recomendación cerrada.' : 'En proceso.');
        await Promise.all([loadRecs(), loadCurrent()]);
    } catch (e) {
        setStatus(e.message, false);
    }
}

function startPing() {
    stopPing();
    const send = async () => {
        const pos = await geo();
        if (!pos) return;
        try {
            await api('/supervision/shifts/ping', { method: 'POST', body: JSON.stringify(pos) });
        } catch (e) {
            setStatus(e.message, false);
        }
    };
    send();
    pingTimer = setInterval(send, 30000);
}

function stopPing() {
    if (pingTimer) clearInterval(pingTimer);
    pingTimer = null;
}

function logout() {
    stopPing();
    stopAllCams();
    localStorage.removeItem('token');
    show('login');
    setStatus('Sesión cerrada.');
}

async function afterLogin(name) {
    setStatus(name ? `Hola ${name}` : 'Sesión activa');
    await Promise.all([loadSites(), loadCatalog(), loadIntake()]);
    const shift = await loadCurrent();
    if (shift) {
        show('ops');
        await loadRecs();
        return;
    }
    show('open-shift');
    startCam('cam-odo', 'environment');
    startCam('cam-self', 'user');
}

document.getElementById('api').value = localStorage.getItem('API_URL') || inferApi();
bindCameras();
document.querySelectorAll('[data-collapse]').forEach((btn) => {
    btn.onclick = () => {
        const body = document.getElementById(btn.dataset.collapse);
        if (!body) return;
        const open = !body.classList.contains('hidden');
        body.classList.toggle('hidden', open);
        btn.classList.toggle('is-open', !open);
    };
});

document.getElementById('btn-login').onclick = async () => {
    try {
        const custom = document.getElementById('api').value.trim().replace(/\/$/, '');
        if (custom) localStorage.setItem('API_URL', custom);
        const data = await api('/supervision/login', {
            method: 'POST',
            body: JSON.stringify({
                email: document.getElementById('email').value,
                password: document.getElementById('password').value,
                device_name: 'supervision-pwa',
            }),
        });
        localStorage.setItem('token', data.token);
        await afterLogin(data.user?.name);
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-start').onclick = async () => {
    try {
        if (!blobs.odo || !blobs.self) throw new Error('Tome foto del odómetro y selfie de inicio.');
        const fd = new FormData();
        fd.append('shift_template_id', document.getElementById('shift-template').value);
        fd.append('zone_id', document.getElementById('route-zone').value);
        fd.append('km_start', document.getElementById('km-start').value);
        fd.append('ppe_checklist', JSON.stringify(readChecks('ppe')));
        fd.append('vehicle_checklist', JSON.stringify(readChecks('vcheck')));
        const vehicleId = document.getElementById('fleet-vehicle').value;
        if (vehicleId) fd.append('vehicle_id', vehicleId);
        else {
            fd.append('vehicle[plate]', document.getElementById('v-plate').value);
            fd.append('vehicle[brand]', document.getElementById('v-brand').value);
            fd.append('vehicle[line]', document.getElementById('v-line').value);
            fd.append('vehicle[model]', document.getElementById('v-model').value);
            fd.append('vehicle[soat_expires_at]', document.getElementById('v-soat').value);
            fd.append('vehicle[technical_review_expires_at]', document.getElementById('v-tm').value);
        }
        fd.append('odometer_photo', blobs.odo, 'odometer.jpg');
        fd.append('selfie_photo', blobs.self, 'selfie.jpg');
        await api('/supervision/shifts/open', { method: 'POST', body: fd });
        blobs.odo = blobs.self = null;
        stopAllCams();
        await loadCurrent();
        show('ops');
        await loadRecs();
        setStatus('Turno iniciado. GPS cada 30 s.');
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-go-close').onclick = () => {
    show('close-shift');
    startCam('cam-odo-end', 'environment');
    startCam('cam-self-end', 'user');
};

document.getElementById('btn-close-back').onclick = () => {
    stopAllCams();
    show('ops');
};

document.getElementById('btn-close').onclick = async () => {
    try {
        if (!blobs.odoEnd || !blobs.selfEnd) throw new Error('Tome foto del odómetro y selfie de cierre.');
        const fd = new FormData();
        fd.append('km_end', document.getElementById('km-end').value);
        fd.append('odometer_photo', blobs.odoEnd, 'odometer-end.jpg');
        fd.append('selfie_photo', blobs.selfEnd, 'selfie-end.jpg');
        await api('/supervision/shifts/close', { method: 'POST', body: fd });
        blobs.odoEnd = blobs.selfEnd = null;
        stopPing();
        stopAllCams();
        activity = null;
        setStatus('Turno cerrado.');
        show('open-shift');
        await loadIntake();
        startCam('cam-odo', 'environment');
        startCam('cam-self', 'user');
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-submit').onclick = async () => {
    if (!currentModule) return;
    try {
        const pos = await geo();
        const clientId = currentModule.requires_client ? siteId() : (siteId() || null);
        if (currentModule.requires_client && !clientId) throw new Error('Seleccione un sitio con Supervisión.');
        if (currentModule.capture === 'reviews') {
            await api('/supervision/reviews', {
                method: 'POST',
                body: JSON.stringify({
                    client_id: clientId,
                    notes: readPayload().notes || '',
                    latitude: pos?.latitude,
                    longitude: pos?.longitude,
                }),
            });
            setStatus('Revista registrada.');
        } else {
            await api('/supervision/logs', {
                method: 'POST',
                body: JSON.stringify({
                    module: currentModule.key,
                    client_id: clientId,
                    payload: readPayload(),
                    latitude: pos?.latitude,
                    longitude: pos?.longitude,
                }),
            });
            setStatus(`${currentModule.label} registrado.`);
        }
        document.getElementById('module-card').classList.add('hidden');
        await Promise.all([loadCurrent(), loadRecs()]);
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-mod-back').onclick = () => document.getElementById('module-card').classList.add('hidden');
document.getElementById('site').onchange = () => loadRecs().catch((e) => setStatus(e.message, false));
document.getElementById('btn-logout').onclick = logout;
document.getElementById('btn-logout-open').onclick = logout;

if (token()) {
    afterLogin().catch((e) => {
        show('login');
        setStatus(e.message, false);
    });
}

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('sw.js').catch(() => {});
}
