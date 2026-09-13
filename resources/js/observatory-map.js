export function observatoryMap(cfg) {
    return {
        mode: 'pins',
        mapType: 'satellite',
        typeFilter: '',
        comuna: cfg.comuna || '',
        comunaQ: '',
        comunaOpen: false,
        comunas: cfg.comunas || [],
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
            const center = cfg.center || { lat: 3.4372, lng: -76.5225 };
            this.map = new google.maps.Map(el, {
                center,
                zoom: Number(cfg.zoom || 12),
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

            if (!this.comuna && (sites.length > 0 || points.length > 0)) {
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
            this.loadComunas();
        },

        loadComunas() {
            if (!this.map || !cfg.layerUrl) {
                return;
            }
            fetch(cfg.layerUrl)
                .then((res) => (res.ok ? res.json() : null))
                .then((geo) => {
                    if (!geo || !this.map?.data) {
                        return;
                    }
                    this.map.data.addGeoJson(geo);
                    this.styleComunas();
                    this.map.data.addListener('click', (event) => {
                        const code = event.feature?.getProperty('code');
                        if (!code) {
                            return;
                        }
                        this.goComuna(code);
                    });
                    this.fitLayer();
                })
                .catch(() => {});
        },

        styleComunas() {
            if (!this.map?.data) {
                return;
            }
            this.map.data.setStyle((feature) => {
                const selected = feature.getProperty('code') === this.comuna;
                return {
                    strokeColor: selected ? '#f8fafc' : '#64748b',
                    strokeOpacity: selected ? 1 : 0.55,
                    strokeWeight: selected ? 2.6 : 1,
                    fillColor: '#38bdf8',
                    fillOpacity: selected ? 0.38 : (this.comuna ? 0.02 : 0.08),
                    clickable: true,
                    visible: !this.comuna || this.comuna === 'fuera' || selected,
                };
            });
        },

        fitLayer() {
            if (!this.map?.data || this.comuna === 'fuera') {
                return;
            }
            const bounds = new google.maps.LatLngBounds();
            let any = false;
            this.map.data.forEach((feature) => {
                const code = feature.getProperty('code');
                if (this.comuna && this.comuna !== 'fuera' && code !== this.comuna) {
                    return;
                }
                feature.getGeometry()?.forEachLatLng((ll) => {
                    bounds.extend(ll);
                    any = true;
                });
            });
            if (any && (this.comuna || !(cfg.sites || []).length)) {
                this.map.fitBounds(bounds, 36);
            }
        },

        comunaRows() {
            return [...this.comunas, { code: 'fuera', name: 'Fuera de Cali' }];
        },

        comunaLabel() {
            if (!this.comuna) {
                return '';
            }
            return this.comunaRows().find((row) => row.code === this.comuna)?.name || this.comuna;
        },

        filteredComunas() {
            const q = this.comunaQ.trim().toLowerCase();
            const rows = this.comunaRows();
            if (!q) {
                return rows;
            }

            return rows.filter((row) => (
                row.code.toLowerCase().includes(q)
                || row.name.toLowerCase().replace(/^comuna\s+0?/, '').includes(q)
                || row.name.toLowerCase().includes(q)
            ));
        },

        goComuna(code) {
            const url = new URL(window.location.href);
            if (!code || this.comuna === code) {
                url.searchParams.delete('comuna');
            } else {
                url.searchParams.set('comuna', code);
            }
            window.location.assign(url.toString());
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
