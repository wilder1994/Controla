window.__observatoryIntakeMapsLoader = window.__observatoryIntakeMapsLoader || null;

function loadGoogleMaps(apiKey) {
    if (window.google?.maps) {
        return Promise.resolve();
    }
    if (window.__observatoryIntakeMapsLoader) {
        return window.__observatoryIntakeMapsLoader;
    }
    window.__observatoryIntakeMapsLoader = new Promise((resolve, reject) => {
        const cb = `__observatoryIntakeMapsReady_${Date.now()}`;
        window[cb] = () => {
            resolve();
            delete window[cb];
        };
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&callback=${cb}`;
        script.async = true;
        script.onerror = () => reject(new Error('No se pudo cargar Google Maps'));
        document.head.appendChild(script);
    });
    return window.__observatoryIntakeMapsLoader;
}

export function observatoryIntake(cfg) {
    return {
        step: cfg.installationId ? 2 : 1,
        query: '',
        sites: [],
        searched: false,
        installationId: cfg.installationId || '',
        installationName: cfg.installationName || '',
        kind: cfg.kind || '',
        anonymous: Boolean(cfg.anonymous),
        latitude: cfg.latitude || '',
        longitude: cfg.longitude || '',
        siteLat: cfg.siteLat ?? null,
        siteLng: cfg.siteLng ?? null,
        hasPin: false,
        mapsKey: cfg.mapsKey || '',
        map: null,
        marker: null,

        init() {
            if (this.step === 2) {
                this.goStep(2);
            }
        },

        async search() {
            const q = this.query.trim();
            if (q.length < 2) {
                this.sites = [];
                this.searched = false;
                return;
            }
            const res = await fetch(cfg.sitesUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } });
            const data = await res.json();
            this.sites = data.sites || [];
            this.searched = true;
        },

        pick(row) {
            this.installationId = String(row.id);
            this.installationName = row.name;
            this.siteLat = row.lat ?? null;
            this.siteLng = row.lng ?? null;
            this.latitude = row.lat != null ? String(row.lat) : '';
            this.longitude = row.lng != null ? String(row.lng) : '';
            this.sites = [];
            this.query = row.name;
        },

        goStep(n) {
            this.step = n;
            if (n === 2) {
                this.$nextTick(() => {
                    setTimeout(() => this.initMap(), 50);
                });
            }
        },

        savePos(ll) {
            this.latitude = String(ll.lat());
            this.longitude = String(ll.lng());
        },

        async initMap() {
            const lat = Number(this.latitude || this.siteLat);
            const lng = Number(this.longitude || this.siteLng);
            this.hasPin = Number.isFinite(lat) && Number.isFinite(lng);
            if (!this.hasPin || !this.mapsKey) {
                return;
            }

            try {
                await loadGoogleMaps(this.mapsKey);
            } catch {
                this.hasPin = false;
                return;
            }

            await this.$nextTick();
            const el = this.$refs.pinMap;
            if (!el || !window.google?.maps) {
                return;
            }

            const pos = { lat, lng };
            if (!this.map) {
                this.map = new google.maps.Map(el, {
                    center: pos,
                    zoom: 18,
                    mapTypeId: google.maps.MapTypeId.SATELLITE,
                    streetViewControl: false,
                    mapTypeControl: false,
                });
                this.marker = new google.maps.Marker({
                    position: pos,
                    map: this.map,
                    draggable: true,
                    title: 'Lugar de la novedad',
                });
                this.marker.addListener('dragend', () => this.savePos(this.marker.getPosition()));
                this.map.addListener('click', (e) => {
                    this.marker.setPosition(e.latLng);
                    this.savePos(e.latLng);
                });
            } else {
                this.marker.setPosition(pos);
                this.map.setCenter(pos);
            }

            google.maps.event.trigger(this.map, 'resize');
            this.map.setCenter(pos);
            this.savePos(this.marker.getPosition());
        },
    };
}
