export function observatoryMap(cfg) {
    return {
        mode: 'pins',
        map: null,
        heatmap: null,
        markers: [],

        init() {
            window.initObservatoryMap = () => this.draw();
            if (window.google?.maps || window.__observatoryMapReady) {
                this.draw();
            }
        },

        esc(value) {
            return String(value ?? '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;',
            }[char]));
        },

        colorFor(status) {
            if (status === 'nuevo') return '#f59e0b';
            if (status === 'en_atencion') return '#6366f1';
            if (status === 'cerrado') return '#64748b';
            return '#94a3b8';
        },

        draw() {
            const el = this.$refs.map;
            if (!el || !window.google?.maps || this.map) {
                return;
            }

            const sites = cfg.sites || [];
            const center = cfg.center || { lat: 4.5709, lng: -74.2973 };
            this.map = new google.maps.Map(el, {
                center,
                zoom: Number(cfg.zoom || 6),
                mapTypeId: google.maps.MapTypeId.SATELLITE,
                streetViewControl: false,
            });

            const bounds = new google.maps.LatLngBounds();
            const heat = [];
            const info = new google.maps.InfoWindow();

            sites.forEach((site) => {
                const pos = { lat: Number(site.lat), lng: Number(site.lng) };
                bounds.extend(pos);
                const marker = new google.maps.Marker({
                    position: pos,
                    map: this.map,
                    title: site.name,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: site.open_count > 0 ? 9 : 7,
                        fillColor: this.colorFor(site.status),
                        fillOpacity: 0.95,
                        strokeColor: '#0f172a',
                        strokeWeight: 1,
                    },
                });
                marker.addListener('click', () => {
                    const link = site.show_url
                        ? `<a href="${this.esc(site.show_url)}">Ver evento</a>`
                        : '<span>Sin eventos</span>';
                    info.setContent(
                        `<div style="color:#0f172a;font:13px/1.4 sans-serif;max-width:220px">`
                        + `<strong>${this.esc(site.name)}</strong><br>`
                        + `${site.client ? this.esc(site.client) + '<br>' : ''}`
                        + `${this.esc(site.status_label)}`
                        + (site.open_count ? ` · ${site.open_count} abiertos` : '')
                        + `<br>${link}</div>`,
                    );
                    info.open(this.map, marker);
                });
                this.markers.push(marker);
                if (site.open_count > 0) {
                    heat.push({ location: new google.maps.LatLng(pos.lat, pos.lng), weight: Number(site.heat_weight || 1) });
                }
            });

            if (sites.length > 0) {
                this.map.fitBounds(bounds, 48);
            }

            if (google.maps.visualization && heat.length > 0) {
                this.heatmap = new google.maps.visualization.HeatmapLayer({
                    data: heat,
                    radius: 42,
                    opacity: 0.65,
                });
            }

            this.applyMode();
        },

        setMode(mode) {
            this.mode = mode;
            this.applyMode();
        },

        applyMode() {
            const pins = this.mode === 'pins';
            this.markers.forEach((marker) => marker.setMap(pins ? this.map : null));
            if (this.heatmap) {
                this.heatmap.setMap(pins ? null : this.map);
            }
        },
    };
}
