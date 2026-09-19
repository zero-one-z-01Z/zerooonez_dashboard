import test from 'node:test';
import assert from 'node:assert/strict';
import { createBoundaryMap, createLocationMap, destroyMaps } from '../../resources/js/back/forms/maps.js';

test('boundary deletion clears stored value and cleanup targets only its own modal handler', () => {
    const events = {}, removed = [];
    globalThis.$ = () => ({ closest: () => ({ off(name) { removed.push(name); return this; }, on() { return this; } }) });
    const map = { setView() { return this; }, addLayer() {}, addControl() {}, on(name, cb) { events[name] = cb; }, remove() {}, invalidateSize() {} };
    globalThis.L = {
        map: () => map, tileLayer: () => ({ addTo() {} }),
        FeatureGroup: class { clearLayers() {} addLayer() {} }, Control: { Draw: class {} },
        polygon: points => ({ getLatLngs: () => [points.map(([lat,lng]) => ({lat,lng}))] }),
    };
    const element = { id: 'edit_boundary_a' };
    let changed = 0; const input = { value: '[[1,2],[3,4],[5,6]]', dispatchEvent() { changed++; } };
    createBoundaryMap(element, input, input.value);
    events['draw:deleted'](); assert.equal(input.value, ''); assert.equal(changed, 1);
    destroyMaps(element);
    assert.equal(removed.at(-1), 'shown.bs.modal.dashboardBoundaryedit_boundary_a');
    delete globalThis.L; delete globalThis.$;
});

test('two location maps retain zero coordinates and destroy listeners independently', () => {
    const positions = [], removed = [];
    globalThis.google = { maps: {
        Map: class { constructor(element, options) { positions.push(options.center); } setCenter() {} },
        Marker: class { setPosition() {} },
        LatLng: class {}, event: { addListener() {}, clearInstanceListeners() {} },
    } };
    const input = value => ({ value, addEventListener() {}, removeEventListener(event) { removed.push(event); } });
    const first = {}, second = {};
    const lat = input('0'), lng = input('0');
    createLocationMap(first, lat, lng); createLocationMap(second, input('12'), input('-2'));
    assert.deepEqual(positions, [{lat:0,lng:0},{lat:12,lng:-2}]);
    destroyMaps(first); assert.equal(removed.length, 2);
    destroyMaps(second); assert.equal(removed.length, 4);
    delete globalThis.google;
});
