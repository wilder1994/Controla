export function opsLiveAlerts() {
    return {
        pollUrl: '',
        panicUrl: '',
        claimUrl: '',
        csrf: '',
        after: 0,
        open: false,
        panicOpen: false,
        title: '',
        body: '',
        type: '',
        alertId: 0,
        canAttend: false,
        note: '',
        lat: '',
        lng: '',
        timer: null,
        alarmTimer: null,
        ctx: null,
        init() {
            const last = Number(localStorage.getItem('ops_alert_after') || '0');
            this.after = Number.isFinite(last) ? last : 0;
            this.locate();
            this.tick();
            this.timer = setInterval(() => this.tick(), 4000);
        },
        locate() {
            if (!navigator.geolocation) return;
            navigator.geolocation.getCurrentPosition((p) => {
                this.lat = p.coords.latitude.toFixed(7);
                this.lng = p.coords.longitude.toFixed(7);
            }, () => {}, { timeout: 4000 });
        },
        async tick() {
            if (!this.pollUrl) return;
            try {
                const res = await fetch(`${this.pollUrl}?after=${this.after}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                if (!res.ok) return;
                const data = await res.json();
                const alerts = Array.isArray(data.alerts) ? data.alerts : [];
                if (!alerts.length || this.open) return;
                const first = alerts[0];
                this.after = first.id;
                localStorage.setItem('ops_alert_after', String(this.after));
                this.alertId = Number(first.id) || 0;
                this.type = first.type || '';
                this.title = first.title;
                this.body = first.body;
                this.canAttend = Boolean(first.can_attend);
                this.open = true;
                if (this.type === 'panic') {
                    this.startAlarm();
                } else {
                    this.beep();
                }
            } catch (_) {}
        },
        startAlarm() {
            this.stopAlarm();
            this.beep();
            this.alarmTimer = setInterval(() => this.beep(), 1200);
        },
        stopAlarm() {
            if (this.alarmTimer) {
                clearInterval(this.alarmTimer);
                this.alarmTimer = null;
            }
        },
        beep() {
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                this.ctx = this.ctx || new Ctx();
                if (this.ctx.state === 'suspended') {
                    this.ctx.resume();
                }
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.value = 880;
                gain.gain.value = 0.08;
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                setTimeout(() => osc.stop(), 700);
            } catch (_) {}
        },
        ack() {
            this.stopAlarm();
            this.open = false;
            this.title = '';
            this.body = '';
            this.canAttend = false;
            this.alertId = 0;
            this.type = '';
        },
        async attend() {
            if (!this.claimUrl || !this.alertId) return;
            const body = new FormData();
            body.append('_token', this.csrf);
            body.append('alert_id', String(this.alertId));
            try {
                const res = await fetch(this.claimUrl, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body,
                    credentials: 'same-origin',
                });
                const data = await res.json().catch(() => ({}));
                this.ack();
                if (res.ok && data.url) {
                    window.location.href = data.url;
                    return;
                }
            } catch (_) {
                this.ack();
            }
        },
        async sendPanic() {
            const body = new FormData();
            body.append('_token', this.csrf);
            if (this.lat) body.append('latitude', this.lat);
            if (this.lng) body.append('longitude', this.lng);
            if (this.note) body.append('note', this.note);
            await fetch(this.panicUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
                credentials: 'same-origin',
            });
            this.panicOpen = false;
            this.note = '';
        },
    };
}
