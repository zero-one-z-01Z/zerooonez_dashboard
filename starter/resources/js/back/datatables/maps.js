/** خرائط نافذة الإضافة عبر Adapters النماذج المشتركة. */

import { createBoundaryMap, createLocationMap } from '../forms/maps.js';

/**
 * يهيئ حدود نموذج الإضافة ويمسح الرسم السابق عند إعادة فتح النافذة.
 * can_edit_by_developer
 */
export function initializeBoundaryMap() {
    const form = document.getElementById('add_form');
    return [...(form?.querySelectorAll('[data-dashboard-boundary]') ?? [])].map(element => createBoundaryMap(element,
        [...form.elements].find(input => input.name === (element.dataset.dashboardBoundary || 'boundary')), null));
}

/** يهيئ نقطة الموقع في نموذج الإضافة مع الإحداثيات الافتراضية الحالية. */
export function initMap() {
    const form = document.getElementById('add_form');
    return [...(form?.querySelectorAll('[data-dashboard-location-map]') ?? [])].map(element => createLocationMap(element,
        [...form.elements].find(input => input.name === (element.dataset.latField || 'lat')),
        [...form.elements].find(input => input.name === (element.dataset.lngField || 'lng'))));
}
