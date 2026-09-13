export function observatoryMap(cfg) {
    return {
        mode: 'pins',
        mapType: 'satellite',
        typeFilter: '',
        map: null,
        heatmap: null,
        markers: [],
        heatSite: [],
        heatRisk: [],

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

        matches(item) {
            if (!this.typeFilter) {
                return true;
            }
            if (item.kind_slug) {
                return item.kind_slug === this.typeFilter;
            }
            return (item.summary || []).some((row) => row.slug === this.typeFilter || row.name);
        },

        draw() {
            const el = this.$refs.map;
            if (!el || !window.google?.maps || this.map) {
                return;
            }

            const sites = cfg.sites || [];
            const points = cfg.points || [];
            const center = cfg.center || { lat: 4.5709, lng: -74.2973 };
            this.map = new google.maps.Map(el, {
                center,
                zoom: Number(cfg.zoom || 6),
                mapTypeId: google.maps.MapTypeId.SATELLITE,
                streetViewControl: false,
                fullscreenControl: false,
                mapTypeControl: false,
                zoomControl: true,
                zoomControlOptions: {
                    position: google.maps.ControlPosition.RIGHT_BOTTOM,
                },
            });

            const bounds = new google.maps.LatLngBounds();
            const info = new google.maps.InfoWindow();
            this.heatSite = [];
            this.heatRisk = [];

            sites.forEach((site) => {
                const pos = { lat: Number(site.lat), lng: Number(site.lng) };
                bounds.extend(pos);
                const marker = new google.maps.Marker({
                    position: pos,
                    map: this.map,
                    title: site.name,
                    zIndex: 1,
                    kindSlugs: site.type_slugs || (site.kind_slug ? [site.kind_slug] : []),
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: site.open_count > 0 ? 8 : 6,
                        fillColor: site.color || '#94a3b8',
                        fillOpacity: 0.9,
                        strokeColor: '#0f172a',
                        strokeWeight: 1,
                    },
                });
                marker.addListener('click', () => {
                    const mix = (site.summary || [])
                        .map((row) => `${this.esc(row.name)} (${row.count})`)
                        .join(' · ');
                    const link = site.show_url
                        ? `<a href="${this.esc(site.show_url)}">Ver evento</a>`
                        : '<span>Sin eventos</span>';
                    info.setContent(
                        `<div style="color:#0f172a;font:13px/1.4 sans-serif;max-width:240px">`
                        + `<strong>${this.esc(site.name)}</strong><br>`
                        + `${site.client ? this.esc(site.client) + '<br>' : ''}`
                        + `${mix || this.esc(site.status_label)}`
                        + `<br>${link}</div>`,
                    );
                    info.open(this.map, marker);
                });
                this.markers.push(marker);
                if (site.open_count > 0) {
                    this.heatSite.push({
                        location: new google.maps.LatLng(pos.lat, pos.lng),
                        weight: Number(site.heat_weight || 1),
                        slug: '',
                    });
                    this.heatRisk.push({
                        location: new google.maps.LatLng(pos.lat, pos.lng),
                        weight: Number(site.risk_weight || 1),
                        slug: '',
                    });
                }
            });

            points.forEach((point) => {
                const pos = { lat: Number(point.lat), lng: Number(point.lng) };
                bounds.extend(pos);
                const marker = new google.maps.Marker({
                    position: pos,
                    map: this.map,
                    title: point.kind || point.title,
                    zIndex: 2,
                    kindSlugs: point.kind_slug ? [point.kind_slug] : [],
                    icon: {
                        path: google.maps.SymbolPath.BACKWARD_CLOSED_ARROW,
                        scale: 5,
                        fillColor: point.color || '#94a3b8',
                        fillOpacity: 1,
                        strokeColor: '#ffffff',
                        strokeWeight: 1,
                    },
                });
                marker.addListener('click', () => {
                    const link = point.show_url
                        ? `<a href="${this.esc(point.show_url)}">Ver evento</a>`
                        : '';
                    info.setContent(
                        `<div style="color:#0f172a;font:13px/1.4 sans-serif;max-width:220px">`
                        + `<strong>${this.esc(point.title)}</strong><br>`
                        + `<span style="color:${this.esc(point.color || '#334155')}">${this.esc(point.kind)}</span>`
                        + ` · ${this.esc(point.status_label)}`
                        + (link ? `<br>${link}` : '')
                        + `</div>`,
                    );
                    info.open(this.map, marker);
                });
                this.markers.push(marker);
                if (point.open) {
                    this.heatSite.push({
                        location: new google.maps.LatLng(pos.lat, pos.lng),
                        weight: 1,
                        slug: point.kind_slug || '',
                    });
                    this.heatRisk.push({
                        location: new google.maps.LatLng(pos.lat, pos.lng),
                        weight: Number(point.level || 1),
                        slug: point.kind_slug || '',
                    });
                }
            });

            if (sites.length > 0 || points.length > 0) {
                this.map.fitBounds(bounds, 48);
            }

            if (google.maps.visualization) {
                this.heatmap = new google.maps.visualization.HeatmapLayer({
                    data: [],
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

        setTypeFilter(slug) {
            this.typeFilter = this.typeFilter === slug ? '' : slug;
            this.applyMode();
        },

        setMapType(type) {
            this.mapType = type;
            if (this.map) {
                this.map.setMapTypeId(type === 'roadmap' ? google.maps.MapTypeId.ROADMAP : google.maps.MapTypeId.SATELLITE);
            }
        },

        applyMode() {
            const pins = this.mode === 'pins';
            this.markers.forEach((marker) => {
                const slugs = marker.kindSlugs || [];
                const ok = !this.typeFilter || slugs.length === 0 || slugs.includes(this.typeFilter);
                marker.setMap(pins && ok ? this.map : null);
            });
            if (!this.heatmap) {
                return;
            }
            if (pins) {
                this.heatmap.setMap(null);
                return;
            }
            const source = this.mode === 'heat_risk' ? this.heatRisk : this.heatSite;
            const data = source.filter((row) => !this.typeFilter || row.slug === this.typeFilter);
            this.heatmap.setData(data);
            this.heatmap.setMap(this.map);
        },
    };
}
