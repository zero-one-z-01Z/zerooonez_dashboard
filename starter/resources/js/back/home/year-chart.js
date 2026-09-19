/** تشغيل الرسم السنوي وربطه باختيار الشهر. */

import { buildYearChartOptions } from './year-chart-options.js';
import { setupMonthSelection } from './month-selection.js';

/**
 * ينشئ الرسم السنوي إن وجد عنصره ثم يربط قائمة اختيار الشهر.
 * @returns {?Object} نسخة ApexCharts أو null عند غياب العنصر.
 */
export function initYearStatisticsChart(data = window) {
    const chartElement = document.querySelector('#yearStatisticsChart');
    if (!chartElement) return null;
    const chart = new ApexCharts(chartElement, buildYearChartOptions(config, data));
    chart.render();
    setupMonthSelection(chart);
    return chart;
}
