export function opsLiveAlerts() {
    return {
        pollUrl: '',
        panicUrl: '',
        csrf: '',
        after: 0,
        open: false,
        panicOpen: false,
        title: '',
        body: '',
        note: '',
        lat: '',
        lng: '',
        timer: null,
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
                this.title = first.title;
                this.body = first.body;
                this.open = true;
                this.beep();
            } catch (_) {}
        },
        beep() {
            try {
                const Ctx = window.AudioContext || window.webkitAudioContext;
                if (!Ctx) return;
                this.ctx = this.ctx || new Ctx();
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'sawtooth';
                osc.frequency.value = 880;
                gain.gain.value = 0.08;
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start();
                setTimeout(() => osc.stop(), 900);
            } catch (_) {}
        },
        ack() {
            this.open = false;
            this.title = '';
            this.body = '';
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
