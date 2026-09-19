/** Adapters متعددة النسخ لخرائط Google Maps وحدود Leaflet داخل النماذج. */

const boundaryMaps = new Map();
const locationMaps = new Map();

function removeLocationEntry(element, entry) {
    entry.latInput?.removeEventListener('change', entry.update);
    entry.lngInput?.removeEventListener('change', entry.update);
    globalThis.google?.maps?.event?.clearInstanceListeners(entry.marker);
    globalThis.google?.maps?.event?.clearInstanceListeners(entry.map);
    locationMaps.delete(element);
}

/** ينظف كل الخرائط التابعة لجذر قبل إزالة محتواه. */
export function destroyMaps(root = document) {
    for (const [element, entry] of [...boundaryMaps]) {
        if (root === element || root.contains?.(element)) {
            $(element).closest('.modal').off(entry.namespace);
            entry.map.remove();
            boundaryMaps.delete(element);
        }
    }
    for (const [element, entry] of [...locationMaps]) {
        if (root === element || root.contains?.(element)) removeLocationEntry(element, entry);
    }
}

/** ينشئ محرر حدود Leaflet مستقلًا ويربطه بالحقل المخفي المحدد. */
export function createBoundaryMap(element, input, value = null) {
    if (!element || !input || !globalThis.L) return null;
    destroyMaps(element);
    const map = L.map(element).setView([24.7136, 46.6753], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors',
    }).addTo(map);
    const drawn = new L.FeatureGroup();
    map.addLayer(drawn);
    map.addControl(new L.Control.Draw({
        edit: { featureGroup: drawn },
        draw: { polygon: true, rectangle: false, circle: false, marker: false, polyline: false },
    }));
    const write = layer => {
        input.value = JSON.stringify(layer.getLatLngs()[0].map(point => [point.lat, point.lng]));
    };
    map.on('draw:created', event => { drawn.clearLayers(); drawn.addLayer(event.layer); write(event.layer); });
    map.on('draw:edited', event => event.layers.eachLayer(write));
    map.on('draw:deleted', () => { input.value = ''; input.dispatchEvent(new Event('change', { bubbles: true })); });

    if (value) {
        try {
            const polygon = typeof value === 'string' ? JSON.parse(value) : value;
            const layer = L.polygon(polygon, { color: 'red' });
            drawn.addLayer(layer);
            if (polygon.length) map.setView([polygon[0][0], polygon[0][1]], 10);
        } catch (error) {
            console.error('Invalid boundary JSON:', error);
        }
    }
    const namespace = 'shown.bs.modal.dashboardBoundary' + String(element.id).replace(/\W/g, '');
    $(element).closest('.modal').off(namespace).on(namespace, () => map.invalidateSize());
    boundaryMaps.set(element, { map, drawn, input, namespace });
    return map;
}

/** ينشئ خريطة موقع Google مستقلة ويزامن العلامة مع حقلي lat وlng. */
export function createLocationMap(element, latInput, lngInput, coordinates = {}) {
    if (!element || !latInput || !lngInput || !globalThis.google?.maps) return null;
    const current = locationMaps.get(element);
    const numeric = (value, fallback) => Number.isFinite(Number.parseFloat(value)) ? Number.parseFloat(value) : fallback;
    const location = { lat: numeric(coordinates.lat ?? latInput.value, 24.774265), lng: numeric(coordinates.lng ?? lngInput.value, 46.738586) };
    latInput.value = location.lat;
    lngInput.value = location.lng;
    if (current) {
        current.marker.setPosition(location);
        current.map.setCenter(location);
        return current.map;
    }
    const map = new google.maps.Map(element, { center: location, zoom: 9 });
    const marker = new google.maps.Marker({ position: location, map, draggable: true });
    const write = position => {
        latInput.value = position.lat();
        lngInput.value = position.lng();
    };
    google.maps.event.addListener(marker, 'dragend', () => write(marker.getPosition()));
    google.maps.event.addListener(map, 'click', event => { marker.setPosition(event.latLng); write(event.latLng); });
    const update = () => {
        const lat = Number.parseFloat(latInput.value);
        const lng = Number.parseFloat(lngInput.value);
        if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;
        const position = new google.maps.LatLng(lat, lng);
        marker.setPosition(position);
        map.setCenter(position);
    };
    latInput.addEventListener('change', update);
    lngInput.addEventListener('change', update);
    locationMaps.set(element, { map, marker, latInput, lngInput, update });
    return map;
}

/**
 * يعرض حدود القيمة المحفوظة داخل نموذج الإضافة أو التعديل الحالي.
 * can_edit_by_developer
 */
export function populateBoundary(formSelector, key, value) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    const input = form.querySelector(`[name="${key}"]`);
    const element = [...form.querySelectorAll('[data-dashboard-boundary]')].find(el => el.dataset.dashboardBoundary === key)
        ?? form.querySelector('[data-dashboard-boundary], [id$="map"]');
    return createBoundaryMap(element, input, value);
}

/** واجهة التوافق القديمة لتهيئة خريطة الموقع من محدد النموذج. */
export function initMap(lat, lng, formSelector) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    const prefix = form.id;
    const mapElement = form.querySelector('[data-dashboard-location-map]') ?? document.getElementById(`${prefix}map`);
    const latInput = form.elements.namedItem(mapElement?.dataset.latField || 'lat') || document.getElementById(`${prefix}lat`);
    const lngInput = form.elements.namedItem(mapElement?.dataset.lngField || 'lng') || document.getElementById(`${prefix}lng`);
    return createLocationMap(
        mapElement,
        latInput,
        lngInput,
        { lat, lng },
    );
}

/** Hydrates all explicitly named coordinates without treating zero as missing. */
export function populateLocationMap(formSelector, key, data) {
    const form = document.querySelector(formSelector);
    if (!form) return;
    for (const element of form.querySelectorAll('[data-dashboard-location-map]')) {
        if (element.dataset.dashboardLocationMap !== key && element.dataset.latField !== key && element.dataset.lngField !== key) continue;
        const lat = element.dataset.latField, lng = element.dataset.lngField;
        createLocationMap(element, form.elements.namedItem(lat), form.elements.namedItem(lng), { lat: data[lat], lng: data[lng] });
    }
}
