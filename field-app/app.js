const statusEl = document.getElementById('status');
let pingTimer = null;
let catalog = [];
let intake = null;
let currentModule = null;
let currentReview = null;
let activity = null;
let lastGeo = null;
let sites = [];
let reviewClient = null;
let reviewPost = null;
let reviewGuard = null;
let streams = {};
let blobs = { odo: null, self: null, odoEnd: null, selfEnd: null, guard: null };
let searchTimers = {};
let moduleReturn = 'home';

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
    if (currentModule?.requires_client) {
        const value = document.getElementById('mod-client')?.value;
        return value ? Number(value) : null;
    }
    return currentReview?.client_id || null;
}

async function geoRequired() {
    const pos = await geo();
    if (pos?.latitude == null || pos?.longitude == null) {
        throw new Error('Active la ubicación del dispositivo para guardar la revista.');
    }
    return pos;
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

function cameraAvailable() {
    return Boolean(window.isSecureContext && navigator.mediaDevices?.getUserMedia);
}

async function startCam(videoId, facing) {
    stopCam(videoId);
    const video = document.getElementById(videoId);
    if (!cameraAvailable()) {
        setStatus('HTTP local: use Tomar foto (evidencia de prueba). Cámara real requiere HTTPS o el celular.');
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
        setStatus('Sin cámara. Tomar foto genera evidencia de prueba.', false);
    }
}

function fakeSnap(imgId, key, label) {
    const canvas = document.createElement('canvas');
    canvas.width = 960;
    canvas.height = 720;
    const ctx = canvas.getContext('2d');
    ctx.fillStyle = '#0b1220';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#243049';
    ctx.fillRect(40, 200, 880, 420);
    ctx.fillStyle = '#fbbf24';
    ctx.font = 'bold 36px sans-serif';
    ctx.fillText(label, 40, 70);
    ctx.fillStyle = '#94a3b8';
    ctx.font = '22px sans-serif';
    ctx.fillText(new Date().toLocaleString('es-CO'), 40, 114);
    ctx.fillText(location.hostname, 40, 148);
    canvas.toBlob((blob) => {
        blobs[key] = blob;
        const img = document.getElementById(imgId);
        img.src = URL.createObjectURL(blob);
        img.classList.remove('hidden');
        setStatus('Foto de prueba lista. En celular con HTTPS se usa la cámara.');
    }, 'image/jpeg', 0.86);
}

function stopCam(videoId) {
    const stream = streams[videoId];
    if (stream) stream.getTracks().forEach((t) => t.stop());
    delete streams[videoId];
}

function stopAllCams() {
    Object.keys(streams).forEach(stopCam);
}

function snapTo(videoId, imgId, key, label) {
    const video = document.getElementById(videoId);
    if (!video.videoWidth) {
        fakeSnap(imgId, key, label || 'Prueba');
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
    document.getElementById('btn-snap-odo').onclick = () => snapTo('cam-odo', 'snap-odo', 'odo', 'Odómetro inicio');
    document.getElementById('btn-cam-self').onclick = () => {
        document.getElementById('snap-self').classList.add('hidden');
        blobs.self = null;
        startCam('cam-self', 'user');
    };
    document.getElementById('btn-snap-self').onclick = () => snapTo('cam-self', 'snap-self', 'self', 'Selfie inicio');
    document.getElementById('btn-cam-odo-end').onclick = () => {
        document.getElementById('snap-odo-end').classList.add('hidden');
        blobs.odoEnd = null;
        startCam('cam-odo-end', 'environment');
    };
    document.getElementById('btn-snap-odo-end').onclick = () => snapTo('cam-odo-end', 'snap-odo-end', 'odoEnd', 'Odómetro cierre');
    document.getElementById('btn-cam-self-end').onclick = () => {
        document.getElementById('snap-self-end').classList.add('hidden');
        blobs.selfEnd = null;
        startCam('cam-self-end', 'user');
    };
    document.getElementById('btn-snap-self-end').onclick = () => snapTo('cam-self-end', 'snap-self-end', 'selfEnd', 'Selfie cierre');
    document.getElementById('btn-cam-guard').onclick = () => {
        document.getElementById('snap-guard').classList.add('hidden');
        blobs.guard = null;
        startCam('cam-guard', 'user');
    };
    document.getElementById('btn-snap-guard').onclick = () => snapTo('cam-guard', 'snap-guard', 'guard', 'Vigilante');
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
    const hubMods = catalog.filter((mod) => mod.hangs_off_review);
    document.getElementById('hub-grid').innerHTML = hubMods.map((mod) => `
        <button type="button" class="mod" data-key="${mod.key}">
            <b>${mod.label}</b>
            <span class="n">${logs[mod.key] || 0} en este turno</span>
        </button>`).join('');
    document.querySelectorAll('#hub-grid .mod').forEach((btn) => {
        btn.onclick = () => openModule(btn.dataset.key, 'review');
    });
    const ctx = document.getElementById('review-context');
    ctx.textContent = currentReview
        ? `${currentReview.client_name} · ${currentReview.post_name || currentReview.location_name || ''} · ${currentReview.employee_name}`
        : '';
}

function showOpsHome() {
    document.getElementById('ops-home').classList.remove('hidden');
    document.getElementById('review-card').classList.add('hidden');
    document.getElementById('module-card').classList.add('hidden');
}

function showReview() {
    document.getElementById('ops-home').classList.add('hidden');
    document.getElementById('module-card').classList.add('hidden');
    document.getElementById('review-card').classList.remove('hidden');
    startCam('cam-guard', 'user');
    loadRecs().catch((e) => setStatus(e.message, false));
}

function openModule(key, from = 'home') {
    currentModule = catalog.find((m) => m.key === key);
    if (!currentModule) return;
    moduleReturn = from;
    document.getElementById('ops-home').classList.add('hidden');
    document.getElementById('review-card').classList.add('hidden');
    const wrap = document.getElementById('module-client-wrap');
    wrap.classList.toggle('hidden', !(currentModule.requires_client || currentModule.key === 'supports'));
    document.getElementById('module-card').classList.remove('hidden');
    document.getElementById('module-title').textContent = currentModule.label;
    document.getElementById('module-hint').textContent = currentModule.hint;
    document.getElementById('module-form').innerHTML = (currentModule.fields || []).map((field) => (
        field.type === 'checkbox' ? fieldControl(field) : `<label>${field.label}</label>${fieldControl(field)}`
    )).join('');
    document.getElementById('module-card').scrollIntoView({ behavior: 'smooth' });
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
    sites = data.sites || [];
    const select = document.getElementById('mod-client');
    if (select) {
        select.innerHTML = sites.map((s) => `<option value="${s.id}">${s.name}</option>`).join('')
            || '<option value="">Sin clientes con Supervisión</option>';
    }
}

function debounceSearch(key, fn) {
    clearTimeout(searchTimers[key]);
    searchTimers[key] = setTimeout(fn, 220);
}

function fillCombo(listId, rows, emptyText, onPick) {
    const list = document.getElementById(listId);
    if (!rows.length) {
        list.innerHTML = `<p class="hint" style="padding:.5rem .65rem;margin:0;">${emptyText}</p>`;
        list.classList.remove('hidden');
        return;
    }
    list.innerHTML = rows.map((row) =>
        `<button type="button" data-id="${row.id}">${row.label}</button>`,
    ).join('');
    list.classList.remove('hidden');
    list.querySelectorAll('button').forEach((btn) => {
        btn.onclick = () => {
            const found = rows.find((r) => String(r.id) === btn.dataset.id);
            list.classList.add('hidden');
            if (found) onPick(found);
        };
    });
}

function filterSites(query) {
    const term = query.trim().toLowerCase();
    return sites
        .filter((s) => term === '' || s.name.toLowerCase().includes(term))
        .slice(0, 20)
        .map((s) => ({ id: s.id, label: s.name, name: s.name }));
}

function selectReviewClient(row) {
    reviewClient = { id: row.id, name: row.name || row.label };
    reviewPost = null;
    document.getElementById('rev-client-q').value = reviewClient.name;
    document.getElementById('rev-post-q').disabled = false;
    document.getElementById('rev-post-q').value = '';
    document.getElementById('rev-post-q').placeholder = 'Escriba el puesto';
}

function selectReviewPost(row) {
    reviewPost = { id: row.id, name: row.name || row.label };
    document.getElementById('rev-post-q').value = row.label || reviewPost.name;
}

function selectReviewGuard(row) {
    reviewGuard = { id: row.id, name: row.name, document_number: row.document_number };
    document.getElementById('rev-guard-doc').value = row.document_number;
    document.getElementById('rev-guard-name').value = row.name;
}

function bindReviewUi() {
    const clientQ = document.getElementById('rev-client-q');
    const postQ = document.getElementById('rev-post-q');
    const guardQ = document.getElementById('rev-guard-doc');
    clientQ.oninput = () => debounceSearch('client', () => {
        reviewClient = null;
        fillCombo('rev-client-list', filterSites(clientQ.value), 'Sin clientes con Supervisión', selectReviewClient);
    });
    clientQ.onfocus = () => fillCombo('rev-client-list', filterSites(clientQ.value), 'Sin clientes con Supervisión', selectReviewClient);
    postQ.oninput = () => debounceSearch('post', () => searchPosts(postQ.value));
    postQ.onfocus = () => { if (reviewClient) searchPosts(postQ.value); };
    guardQ.oninput = () => debounceSearch('guard', () => searchGuards(guardQ.value));
    document.addEventListener('click', (ev) => {
        if (!ev.target.closest('.combo')) {
            document.querySelectorAll('.combo-list').forEach((el) => el.classList.add('hidden'));
        }
    });
    document.getElementById('btn-save-review').onclick = saveReview;
}

async function searchPosts(query) {
    if (!reviewClient) {
        fillCombo('rev-post-list', [], 'Primero el cliente', selectReviewPost);
        return;
    }
    try {
        const data = await api(`/supervision/posts?client_id=${reviewClient.id}&q=${encodeURIComponent(query || '')}`);
        const rows = (data.posts || []).map((p) => ({
            id: p.id,
            name: p.name,
            label: p.label || [p.installation_name, p.name].filter(Boolean).join(' · '),
        }));
        fillCombo('rev-post-list', rows, 'Este cliente no tiene puestos', selectReviewPost);
    } catch (e) {
        setStatus(e.message, false);
    }
}

async function searchGuards(document) {
    const term = (document || '').replace(/\s+/g, '');
    if (term.length < 3) {
        reviewGuard = null;
        document.getElementById('rev-guard-name').value = '';
        document.getElementById('rev-guard-list').classList.add('hidden');
        return;
    }
    try {
        const data = await api(`/supervision/guards?document=${encodeURIComponent(term)}`);
        const rows = (data.guards || []).map((g) => ({
            id: g.id,
            name: g.name,
            document_number: g.document_number,
            label: `${g.document_number} · ${g.name}`,
        }));
        fillCombo('rev-guard-list', rows, 'Sin vigilantes con esa cédula', selectReviewGuard);
    } catch (e) {
        setStatus(e.message, false);
    }
}

async function saveReview() {
    try {
        if (!reviewClient) throw new Error('Seleccione el cliente.');
        if (!reviewPost) throw new Error('Seleccione el puesto.');
        if (!reviewGuard) throw new Error('Seleccione el vigilante por cédula.');
        if (!blobs.guard) throw new Error('Tome la foto del vigilante.');
        const pos = await geoRequired();
        const fd = new FormData();
        fd.append('client_id', String(reviewClient.id));
        fd.append('supervisor_post_id', String(reviewPost.id));
        fd.append('employee_id', String(reviewGuard.id));
        fd.append('notes', document.getElementById('rev-notes').value || '');
        fd.append('has_novelty', document.getElementById('rev-novelty').checked ? '1' : '0');
        fd.append('latitude', String(pos.latitude));
        fd.append('longitude', String(pos.longitude));
        fd.append('guard_photo', blobs.guard, 'guard.jpg');
        const data = await api('/supervision/reviews', { method: 'POST', body: fd });
        currentReview = data.review;
        activity = data.activity || activity;
        blobs.guard = null;
        document.getElementById('snap-guard').classList.add('hidden');
        renderHub();
        await loadRecs();
        startCam('cam-guard', 'user');
        setStatus('Revista guardada.');
    } catch (e) {
        setStatus(e.message, false);
    }
}

function enterOps() {
    show('ops');
    showOpsHome();
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
    currentReview = data.current_review || null;
    const shift = data.shift;
    const nameEl = document.getElementById('sup-name');
    if (nameEl) nameEl.textContent = data.supervisor?.name || '';
    if (data.supervisor?.has_selfie) loadSupervisorSelfie();
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
    document.getElementById('km-end').value = shift.km_start || '';
    renderHub();
    startPing();
    return shift;
}

async function loadSupervisorSelfie() {
    try {
        const res = await fetch(`${apiBase()}/supervision/shift-photo/start-selfie`, {
            headers: { Authorization: `Bearer ${token()}`, Accept: 'image/*' },
        });
        if (!res.ok) return;
        const blob = await res.blob();
        document.getElementById('sup-avatar').src = URL.createObjectURL(blob);
    } catch (e) {
        // sin foto de perfil
    }
}

async function loadRecs() {
    const root = document.getElementById('recs');
    const id = currentReview?.client_id;
    if (!id) {
        root.innerHTML = '';
        return;
    }
    const data = await api(`/supervision/recommendations?client_id=${id}`);
    const rows = data.recommendations || [];
    if (!rows.length) {
        root.innerHTML = '';
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
            // ping silencioso
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
        enterOps();
        return;
    }
    show('open-shift');
    startCam('cam-odo', 'environment');
    startCam('cam-self', 'user');
}

document.getElementById('api').value = localStorage.getItem('API_URL') || inferApi();
bindCameras();
bindReviewUi();
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
        enterOps();
        setStatus('Turno iniciado.');
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
    showOpsHome();
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
        currentReview = null;
        setStatus('Turno cerrado.');
        logout();
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-submit').onclick = async () => {
    if (!currentModule) return;
    try {
        const pos = await geo();
        const body = {
            module: currentModule.key,
            payload: readPayload(),
            latitude: pos?.latitude,
            longitude: pos?.longitude,
        };
        if (currentModule.hangs_off_review) {
            if (!currentReview?.id) throw new Error('Guarde la revista de este puesto primero.');
            body.supervisor_shift_review_id = currentReview.id;
        } else if (currentModule.requires_client) {
            const clientId = siteId();
            if (!clientId) throw new Error('Seleccione el cliente.');
            body.client_id = clientId;
        } else {
            const clientId = siteId();
            if (clientId) body.client_id = clientId;
        }
        await api('/supervision/logs', { method: 'POST', body: JSON.stringify(body) });
        setStatus(`${currentModule.label} registrado.`);
        await Promise.all([loadCurrent(), loadRecs()]);
        if (moduleReturn === 'review') showReview();
        else showOpsHome();
    } catch (e) {
        setStatus(e.message, false);
    }
};

document.getElementById('btn-mod-back').onclick = () => {
    if (moduleReturn === 'review') showReview();
    else showOpsHome();
};
document.getElementById('btn-open-review').onclick = showReview;
document.getElementById('btn-review-back').onclick = showOpsHome;
document.getElementById('btn-open-alarms').onclick = () => openModule('alarms', 'home');
document.getElementById('btn-open-supports').onclick = () => openModule('supports', 'home');
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
