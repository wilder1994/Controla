const COMMUNE_CITIES = new Set([
    'armenia',
    'barranquilla',
    'bucaramanga',
    'cali',
    'cartagena',
    'cartagena de indias',
    'cucuta',
    'ibague',
    'manizales',
    'medellin',
    'monteria',
    'neiva',
    'pasto',
    'pereira',
    'popayan',
    'santa marta',
    'santiago de cali',
    'sincelejo',
    'valledupar',
    'villavicencio',
]);

const LABELS = {
    comuna: 'Comuna',
    localidad: 'Localidad',
    vereda: 'Vereda',
    corregimiento: 'Corregimiento',
    none: 'Área',
};

export function normalizePlace(value) {
    return String(value || '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/\p{M}/gu, '')
        .trim();
}

export function classifyArea(name, city) {
    const n = normalizePlace(name);
    const c = normalizePlace(city);

    if (!n) {
        return 'none';
    }
    if (n.includes('vereda')) {
        return 'vereda';
    }
    if (n.includes('corregimiento')) {
        return 'corregimiento';
    }
    if (n.includes('localidad')) {
        return 'localidad';
    }
    if (n.includes('comuna')) {
        return 'comuna';
    }
    if (c.startsWith('bogota') || c === 'distrito capital') {
        return 'localidad';
    }
    if (COMMUNE_CITIES.has(c)) {
        return 'comuna';
    }

    return 'none';
}

export function areaKindLabel(kind) {
    return LABELS[kind] || LABELS.none;
}

export function pickArea(components, city) {
    const get = (type) => {
        const row = (components || []).find((item) => item.types.includes(type));
        return row ? row.long_name : '';
    };
    const cityNorm = normalizePlace(city);
    const ranked = [
        get('administrative_area_level_3'),
        get('sublocality_level_1'),
        get('sublocality'),
    ].filter((name) => name && normalizePlace(name) !== cityNorm);

    for (const name of ranked) {
        const kind = classifyArea(name, city);
        if (kind !== 'none') {
            return { name, kind };
        }
    }

    return { name: '', kind: 'none' };
}

export function installationAreaFields(config) {
    return {
        commune: config.commune || '',
        city: config.city || '',
        areaKind: classifyArea(config.commune || '', config.city || ''),
        get areaLabel() {
            return areaKindLabel(this.areaKind);
        },
        get areaHint() {
            if (this.areaKind !== 'none') {
                return 'Sale del mapa. Puedes corregirlo.';
            }
            if (this.commune) {
                return 'No parece comuna, localidad, vereda o corregimiento. Escríbelo con esa palabra (ej. Vereda El Cerrito).';
            }

            return 'Se llena con la dirección o el pin. En un pueblo puede no aplicar.';
        },
        applyPlace(detail) {
            if (detail.city) {
                this.city = detail.city;
            }
            if (detail.area) {
                this.commune = detail.area;
            } else if (detail.areaKind === 'none') {
                this.commune = '';
            }
            this.refreshKind();
        },
        refreshKind() {
            this.areaKind = classifyArea(this.commune, this.city);
        },
    };
}
