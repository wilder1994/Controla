window.__geoMapsLoader = window.__geoMapsLoader || null;

function loadGoogleMapsPlaces(apiKey) {
    if (window.google?.maps?.places) {
        return Promise.resolve();
    }
    if (window.__geoMapsLoader) {
        return window.__geoMapsLoader;
    }
    window.__geoMapsLoader = new Promise((resolve, reject) => {
        const cb = `__geoMapsReady_${Date.now()}`;
        window[cb] = () => {
            resolve();
            delete window[cb];
        };
        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&callback=${cb}`;
        script.async = true;
        script.onerror = () => reject(new Error('No se pudo cargar Google Maps'));
        document.head.appendChild(script);
    });
    return window.__geoMapsLoader;
}

function parsePlaceComponents(components) {
    const get = (type) => {
        const c = (components || []).find((x) => x.types.includes(type));
        return c ? c.long_name : '';
    };
    const route = get('route');
    const number = get('street_number');
    const address = [route, number].filter(Boolean).join(' ') || '';
    const city = get('locality') || get('administrative_area_level_2') || get('sublocality') || '';
    const department = get('administrative_area_level_1') || '';

    return { address, city, department };
}

window.geoAddressPicker = function geoAddressPicker(config) {
    return {
        address: config.address ?? '',
        city: config.city ?? '',
        department: config.department ?? '',
        latitude: config.latitude !== null && config.latitude !== '' ? Number(config.latitude) : null,
        longitude: config.longitude !== null && config.longitude !== '' ? Number(config.longitude) : null,
        maps: config.maps || {},
        open: false,
        map: null,
        marker: null,
        autocomplete: null,
        draftLat: null,
        draftLng: null,
        draftAddress: '',
        draftCity: '',
        draftDepartment: '',
        draftLabel: '',

        async openMap() {
            this.open = true;
            this.draftLat = this.latitude;
            this.draftLng = this.longitude;
            this.draftAddress = this.address || '';
            this.draftCity = this.city || '';
            this.draftDepartment = this.department || '';
            this.draftLabel = this.address || '';
            await this.$nextTick();
            if (!this.maps.apiKey) {
                return;
            }
            try {
                await loadGoogleMapsPlaces(this.maps.apiKey);
                this.initMap();
            } catch (e) {
                console.error(e);
            }
        },

        closeMap() {
            this.open = false;
        },

        initMap() {
            const el = this.$refs.map;
            if (!el || !window.google?.maps) {
                return;
            }

            const hasCoords = this.draftLat !== null && this.draftLng !== null;
            const center = hasCoords
                ? { lat: Number(this.draftLat), lng: Number(this.draftLng) }
                : { lat: Number(this.maps.center.lat), lng: Number(this.maps.center.lng) };

            this.map = new google.maps.Map(el, {
                center,
                zoom: hasCoords ? 16 : Number(this.maps.zoom || 6),
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false,
            });

            this.marker = new google.maps.Marker({
                map: this.map,
                position: center,
                draggable: true,
            });

            this.marker.addListener('dragend', () => {
                const pos = this.marker.getPosition();
                this.setDraftFromLatLng(pos.lat(), pos.lng());
            });

            this.map.addListener('click', (e) => {
                this.marker.setPosition(e.latLng);
                this.setDraftFromLatLng(e.latLng.lat(), e.latLng.lng());
            });

            if (this.$refs.search) {
                this.autocomplete = new google.maps.places.Autocomplete(this.$refs.search, {
                    fields: ['formatted_address', 'geometry', 'address_components', 'name'],
                    componentRestrictions: { country: 'co' },
                });
                this.autocomplete.bindTo('bounds', this.map);
                this.autocomplete.addListener('place_changed', () => {
                    const place = this.autocomplete.getPlace();
                    if (!place.geometry?.location) {
                        return;
                    }
                    const lat = place.geometry.location.lat();
                    const lng = place.geometry.location.lng();
                    this.map.setCenter(place.geometry.location);
                    this.map.setZoom(16);
                    this.marker.setPosition(place.geometry.location);
                    const parsed = parsePlaceComponents(place.address_components);
                    this.draftLat = lat;
                    this.draftLng = lng;
                    this.draftAddress = parsed.address || place.formatted_address || place.name || '';
                    this.draftCity = parsed.city;
                    this.draftDepartment = parsed.department;
                    this.draftLabel = place.formatted_address || place.name || this.draftAddress;
                });
            }

            if (hasCoords) {
                this.draftLabel = this.address || `${this.draftLat}, ${this.draftLng}`;
            }
        },

        setDraftFromLatLng(lat, lng) {
            this.draftLat = lat;
            this.draftLng = lng;
            this.draftLabel = `${lat.toFixed(5)}, ${lng.toFixed(5)}`;
            const geocoder = new google.maps.Geocoder();
            geocoder.geocode({ location: { lat, lng } }, (results, status) => {
                if (status !== 'OK' || !results?.[0]) {
                    return;
                }
                const parsed = parsePlaceComponents(results[0].address_components);
                this.draftAddress = parsed.address || results[0].formatted_address || this.draftAddress;
                this.draftCity = parsed.city || this.draftCity;
                this.draftDepartment = parsed.department || this.draftDepartment;
                this.draftLabel = results[0].formatted_address || this.draftLabel;
            });
        },

        confirmPlace() {
            if (this.draftLat === null || this.draftLng === null) {
                return;
            }
            this.latitude = this.draftLat;
            this.longitude = this.draftLng;
            if (this.draftAddress) {
                this.address = this.draftAddress;
            }
            if (this.draftCity) {
                this.city = this.draftCity;
            }
            if (this.draftDepartment) {
                this.department = this.draftDepartment;
            }
            this.closeMap();
        },
    };
};
