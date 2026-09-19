/** مدخل Vite لرسوم الصفحة الرئيسية بعد جاهزية DOM. */

import { initYearStatisticsChart } from './home/year-chart.js';
import { initcircleChart } from './home/circle-chart.js';
import { initAverageDailySalesChart } from './home/daily-sales-chart.js';
import { readJsonConfig } from './core/config.js';

/**
 * ينشئ الرسوم الثلاثة بترتيب الصفحة باستخدام البيانات العامة التي تضعها Blade.
 * @returns {void} ينشئ الرسوم الثلاثة بعناصر الصفحة المتاحة؛ لا ينشر نسخها على window.
 */
function initializeDashboardCharts() {
    const data = readJsonConfig('dashboard-home-config', {});
    initYearStatisticsChart(data);
    if (data.circle_chart) initcircleChart(data.circle_chart);
    initAverageDailySalesChart({
        series: data.avg,
        height: 105,
        colors: [config.colors.success]
    });
}

document.addEventListener('DOMContentLoaded', initializeDashboardCharts);
