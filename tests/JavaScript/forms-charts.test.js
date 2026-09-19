import test from 'node:test';
import assert from 'node:assert/strict';
import { setImmediate as nextTurn } from 'node:timers/promises';
import { buildYearChartOptions } from '../../resources/js/back/home/year-chart-options.js';
import { initcircleChart, updatecircleChart } from '../../resources/js/back/home/circle-chart.js';
import { initAverageDailySalesChart, updateAverageDailySalesChart } from '../../resources/js/back/home/daily-sales-chart.js';
import { fetchMonthDataFromLaravel } from '../../resources/js/back/home/month-selection.js';

const theme = {
    fontFamily: 'Dashboard font',
    colors: { textMuted: '#111', headingColor: '#222', borderColor: '#333', bodyColor: '#444', white: '#fff', primary: '#555', warning: '#666', success: '#777', cardColor: '#888' }
};

/** Captures ApexCharts options and operations while preserving the chart instance contract. */
function installChartFixture() {
    const calls = [];
    globalThis.document = { querySelector: id => ({ id }) };
    globalThis.ApexCharts = class {
        constructor(element, options) { this.element = element; this.options = options; calls.push(this); }
        render() { this.rendered = true; }
        updateOptions(options) { this.updatedOptions = options; }
        updateSeries(series) { this.updatedSeries = series; }
    };
    return calls;
}

test('year chart maps both server series and theme settings without mutating source data', () => {
    const data = { thisYear: 2026, previousYear: 2025, thisYearData: [4, 7], previousYearData: [3, 6], categories: ['Jan', 'Feb'] };
    const before = JSON.stringify(data);
    const options = buildYearChartOptions(theme, data);
    assert.deepEqual(options.series, [
        { name: 2026, type: 'column', data: [4, 7] },
        { name: 2025, type: 'line', data: [3, 6] }
    ]);
    assert.deepEqual(options.xaxis.categories, ['Jan', 'Feb']);
    assert.deepEqual(options.colors, [theme.colors.warning, theme.colors.primary]);
    assert.equal(options.legend.fontFamily, theme.fontFamily);
    assert.equal(options.yaxis.labels.formatter(18.2), 18.2);
    assert.equal(options.responsive.at(-1).options.chart.height, 250);
    assert.equal(JSON.stringify(data), before);
});

test('circle and daily charts render supplied data and preserve update payload shapes', () => {
    const calls = installChartFixture();
    const circle = initcircleChart({ labels: ['Open', 'Closed'], series: [3, 5], colors: ['red', 'green'], title: '8', description: 'Orders' }, theme);
    assert.equal(circle.rendered, true);
    assert.equal(circle.options.plotOptions.pie.donut.labels.total.formatter(), '8');
    assert.equal(circle.options.plotOptions.pie.donut.labels.total.label, 'Orders');
    assert.equal(circle.options.legend.labels.colors, theme.colors.headingColor);
    updatecircleChart(circle, { series: [4, 5], labels: ['New', 'Done'] });
    assert.deepEqual(circle.updatedSeries, [4, 5]);
    assert.deepEqual(circle.updatedOptions, { labels: ['New', 'Done'] });
    updatecircleChart(circle, { title: '9' });
    assert.equal(circle.updatedOptions.plotOptions.pie.donut.labels.total.formatter(), '9');
    const daily = initAverageDailySalesChart({ series: [20, 30], height: 120 }, theme);
    assert.equal(daily.rendered, true);
    assert.equal(daily.options.chart.height, 120);
    assert.deepEqual(daily.options.series, [{ data: [20, 30] }]);
    updateAverageDailySalesChart(daily, { series: [10, 12] });
    assert.deepEqual(daily.updatedSeries, [{ data: [10, 12] }]);
    updateAverageDailySalesChart(daily, { colors: ['blue'] });
    assert.deepEqual(daily.updatedOptions, { colors: ['blue'] });
    assert.equal(calls.length, 2);
});

test('missing optional chart elements do not construct ApexCharts instances', () => {
    const calls = installChartFixture();
    document.querySelector = () => null;
    assert.equal(initcircleChart({ labels: [], series: [], colors: [] }, theme), null);
    assert.equal(initAverageDailySalesChart({}, theme), null);
    assert.equal(calls.length, 0);
});

test('month request keeps endpoint/body contract and updates categories, series and subtitle', async () => {
    const requests = [];
    const options = [];
    const series = [];
    const subtitle = { textContent: '' };
    globalThis.document = { querySelector: () => subtitle };
    globalThis.fetch = async (url, request) => {
        requests.push({ url, request });
        return { ok: true, json: async () => ({ categories: ['Week 1'], shipment: [8], delivery: [7], total_deliveries: 7 }) };
    };
    fetchMonthDataFromLaravel('January', { updateOptions: value => options.push(value), updateSeries: value => series.push(value) });
    await nextTurn();
    assert.equal(requests[0].url, '/api/shipment-statistics');
    assert.equal(requests[0].request.method, 'POST');
    assert.deepEqual(JSON.parse(requests[0].request.body), { month: 'January' });
    assert.deepEqual(options.at(-1), { xaxis: { categories: ['Week 1'] } });
    assert.deepEqual(series[0], [
        { name: 'Shipment', type: 'column', data: [8] },
        { name: 'Delivery', type: 'line', data: [7] }
    ]);
    assert.equal(subtitle.textContent, 'Total number of deliveries 7');
});
