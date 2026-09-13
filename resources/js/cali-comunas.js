let cache = null;

export function caliComunasUrl() {
    return document.querySelector('[data-cali-comunas-url]')?.getAttribute('data-cali-comunas-url')
        || '/geo/cali-comunas.geojson';
}

export async function fetchCaliComunas(url) {
    if (cache) {
        return cache;
    }
    const res = await fetch(url || caliComunasUrl());
    if (!res.ok) {
        return null;
    }
    cache = await res.json();

    return cache;
}

function ringContains(lat, lng, ring) {
    if (!Array.isArray(ring) || ring.length < 3) {
        return false;
    }
    let inside = false;
    for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
        const xi = Number(ring[i][0]);
        const yi = Number(ring[i][1]);
        const xj = Number(ring[j][0]);
        const yj = Number(ring[j][1]);
        const hit = ((yi > lat) !== (yj > lat))
            && (lng < ((xj - xi) * (lat - yi)) / ((yj - yi) || 1e-12) + xi);
        if (hit) {
            inside = !inside;
        }
    }

    return inside;
}

function polygonContains(lat, lng, rings) {
    if (!Array.isArray(rings) || !ringContains(lat, lng, rings[0] || [])) {
        return false;
    }
    for (let i = 1; i < rings.length; i++) {
        if (ringContains(lat, lng, rings[i])) {
            return false;
        }
    }

    return true;
}

export function locateCaliComuna(lat, lng, geo) {
    if (!geo?.features || lat == null || lng == null) {
        return null;
    }
    for (const feature of geo.features) {
        const geom = feature.geometry;
        if (!geom) {
            continue;
        }
        const hit = geom.type === 'Polygon'
            ? polygonContains(lat, lng, geom.coordinates)
            : (geom.coordinates || []).some((rings) => polygonContains(lat, lng, rings));
        if (hit) {
            return {
                code: feature.properties?.code || '',
                name: feature.properties?.name || '',
            };
        }
    }

    return null;
}

export function paintCaliLayer(map, geo, selectedCode) {
    if (!map?.data || !geo) {
        return;
    }
    map.data.forEach((feature) => map.data.remove(feature));
    map.data.addGeoJson(geo);
    map.data.setStyle((feature) => {
        const selected = feature.getProperty('code') === selectedCode;
        return {
            strokeColor: selected ? '#f8fafc' : '#94a3b8',
            strokeOpacity: 0.9,
            strokeWeight: selected ? 2.2 : 1,
            fillColor: '#38bdf8',
            fillOpacity: selected ? 0.28 : 0.07,
            clickable: false,
        };
    });
}

export async function attachCaliLayer(map, selectedCode, url) {
    const geo = await fetchCaliComunas(url);
    if (!geo) {
        return null;
    }
    paintCaliLayer(map, geo, selectedCode);

    return locateCaliComuna(
        map.getCenter()?.lat(),
        map.getCenter()?.lng(),
        geo,
    );
}

window.attachCaliLayer = attachCaliLayer;
window.locateCaliComuna = locateCaliComuna;
window.fetchCaliComunas = fetchCaliComunas;
window.paintCaliLayer = paintCaliLayer;
