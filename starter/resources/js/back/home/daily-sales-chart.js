/** إنشاء وتحديث الرسم المصغر لمتوسط المبيعات اليومي. */

/**
 * ينشئ رسم متوسط المبيعات المصغر، مع بيانات افتراضية عند غياب السلسلة أو الارتفاع أو الألوان.
 * @param {Object} customData إعدادات series وheight وcolors.
 * @param {Object} [theme=config] ألوان القالب.
 * @returns {?Object} نسخة الرسم أو null عند غياب averageDailySales.
 */
export function initAverageDailySalesChart(customData, theme = config) {

    var defaultData = {
        series: [500, 160, 930, 670],
        height: 105,
        colors: [theme.colors.success]
    };

    var chartData = {
        series: customData.series || defaultData.series,
        height: customData.height || defaultData.height,
        colors: customData.colors || defaultData.colors
    };

    var averageDailySalesChartEl = document.querySelector("#averageDailySales");

    if (averageDailySalesChartEl !== null) {

        var averageDailySalesOptions = {
            chart: {
                height: chartData.height,
                type: "area",
                toolbar: {
                    show: false
                },
                sparkline: {
                    enabled: true
                }
            },
            markers: {
                colors: "transparent",
                strokeColors: "transparent"
            },
            grid: {
                show: false
            },
            colors: chartData.colors,
            fill: {
                type: "gradient",
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    gradientToColors: [theme.colors.cardColor],
                    opacityTo: 0.1,
                    stops: [0, 100]
                }
            },
            dataLabels: {
                enabled: false
            },
            stroke: {
                width: 2,
                curve: "smooth"
            },
            series: [{
                data: chartData.series
            }],
            xaxis: {
                show: true,
                lines: {
                    show: false
                },
                labels: {
                    show: false
                },
                stroke: {
                    width: 0
                },
                axisBorder: {
                    show: false
                }
            },
            yaxis: {
                stroke: {
                    width: 0
                },
                show: false
            },
            tooltip: {
                enabled: false
            },
            responsive: [
                {
                    breakpoint: 1387,
                    options: {
                        chart: {
                            height: 80
                        }
                    }
                },
                {
                    breakpoint: 1200,
                    options: {
                        chart: {
                            height: 123
                        }
                    }
                }
            ]
        };

        var averageDailySalesChart = new ApexCharts(averageDailySalesChartEl, averageDailySalesOptions);
        averageDailySalesChart.render();

        return averageDailySalesChart;
    }

    return null;
}

/**
 * يحدّث سلسلة المتوسط اليومي أو ألوان الرسم أو ارتفاعه دون إنشائه مجددًا.
 * @param {?Object} chartInstance نسخة الرسم.
 * @param {Object} newData الحقول المراد تعديلها.
 * @returns {void} يحدث الخيارات والسلاسل المقدمة في المثيل الموجود، دون طلب شبكة أو إنشاء رسم بديل.
 */
export function updateAverageDailySalesChart(chartInstance, newData) {
    if (chartInstance) {
        if (newData.series) {
            chartInstance.updateSeries([{
                data: newData.series
            }]);
        }

        if (newData.colors) {
            chartInstance.updateOptions({
                colors: newData.colors
            });
        }

        if (newData.height) {
            chartInstance.updateOptions({
                chart: {
                    height: newData.height
                }
            });
        }
    }
}

/**
 * يجلب تحديث المتوسط اليومي من المسار التجريبي القديم عند استدعائه صراحة.
 * @param {Object} chartInstance نسخة الرسم المراد تحديثها.
 * @returns {void} لا ينفذ أي طلب تلقائي عند الاستيراد.
 */
export function fetchDailySalesDataAndUpdateChart(chartInstance) {

    fetch('/api/average-daily-sales')
        .then(response => response.json())
        .then(data => {
            updateAverageDailySalesChart(chartInstance, data);
        })
        .catch(error => {
            console.error('Error fetching average daily sales data:', error);
        });
}
