/** إنشاء وتحديث الرسم الدائري؛ جلب البيانات اختياري. */

/**
 * ينشئ الرسم الدائري من بيانات Blade ويطبق ألوان وخط القالب.
 * @param {Object} customData labels وseries وcolors وtitle وdescription.
 * @param {Object} [theme=config] ألوان وخط القالب.
 * @returns {?Object} نسخة الرسم أو null عندما لا يوجد عنصر circleChart.
 */
export function initcircleChart(customData, theme = config) {

    var chartData = {
        labels: customData.labels ,
        series: customData.series ,
        colors: customData.colors ,
        title: customData.title,
        description: customData.description,
    };

    var circleChartEl = document.querySelector("#circleChart");

    if (circleChartEl !== null) {

        var chartOptions = {
            chart: {
                height: 391,
                parentHeightOffset: 0,
                type: "donut"
            },
            labels: chartData.labels,
            series: chartData.series,
            colors: chartData.colors,
            stroke: {
                width: 0
            },
            dataLabels: {
                enabled: false,
                /**
                 * ينسق نسبة الجزء كعدد صحيح لعنوان البيانات.
                 * @param {number|string} e النسبة التي حسبتها ApexCharts.
                 * @param {object} t سياق ApexCharts؛ غير مستخدم في التقريب.
                 * @returns {number} ناتج parseInt للنسبة.
                 */
                formatter: function(e, t) {
                    return parseInt(e)
                }
            },
            legend: {
                show: true,
                position: "bottom",
                offsetY: 15,
                markers: {
                    width: 8,
                    height: 8,
                    offsetX: -3
                },
                itemMargin: {
                    horizontal: 15,
                    vertical: 8
                },
                fontSize: "13px",
                fontFamily: theme.fontFamily,
                fontWeight: 400,
                labels: {
                    colors: theme.colors.headingColor,
                    useSeriesColors: false
                }
            },
            tooltip: {
                theme: "dark"
            },
            grid: {
                padding: {
                    top: 15
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: "77%",
                        labels: {
                            show: true,
                            value: {
                                fontSize: "24px",
                                fontFamily: theme.fontFamily,
                                color: theme.colors.headingColor,
                                fontWeight: 500,
                                offsetY: -20,
                                /**
                                 * يعرض قيمة الجزء المحدد كعدد صحيح وسط الرسم.
                                 * @param {number|string} e قيمة الجزء من ApexCharts.
                                 * @returns {number} القيمة بعد parseInt.
                                 */
                            formatter: function(e) {
                                    return parseInt(e)
                                }
                            },
                            name: {
                                offsetY: 30,
                                fontFamily: theme.fontFamily
                            },
                            total: {
                                show: true,
                                fontSize: "15px",
                                fontFamily: theme.fontFamily,
                                color: theme.colors.bodyColor,
                                label: chartData.description,
                                /**
                                 * يعرض عنوان البيانات في منتصف الرسم بدل جمع قيم السلاسل.
                                 * @param {object} e سياق ApexCharts؛ يعتمد العرض على chartData.title.
                                 * @returns {string|number} عنوان المنتصف المرسل من Blade.
                                 */
                            formatter: function(e) {
                                    return chartData.title;
                                }
                            }
                        }
                    }
                }
            },
            responsive: [{
                breakpoint: 420,
                options: {
                    chart: {
                        height: 360
                    }
                }
            }]
        };

        var circleChart = new ApexCharts(circleChartEl, chartOptions);
        circleChart.render();

        return circleChart;
    }

    return null;
}

/**
 * يحدّث سلاسل الرسم الدائري وتسمياته وقيمة المنتصف عند ورود بيانات جديدة.
 * @param {?Object} chart نسخة الرسم الحالية.
 * @param {Object} newData الحقول المراد تحديثها من series وlabels وtitle.
 * @returns {void} يحدث الرسم الموجود فقط؛ يفوض عمليات الرسم غير المتزامنة إلى ApexCharts دون إرجاع وعودها.
 */
export function updatecircleChart(chart, newData) {
    if (chart) {
        if (newData.series) {
            chart.updateSeries(newData.series);
        }

        if (newData.labels) {
            chart.updateOptions({
                labels: newData.labels
            });
        }

        if (newData.title) {
            chart.updateOptions({
                plotOptions: {
                    pie: {
                        donut: {
                            labels: {
                                total: {
                                    /**
                                     * يعرض عنوان المنتصف من آخر تحديث للرسم.
                                     * @returns {string|number} newData.title الملتقط وقت التحديث.
                                     */
                            formatter: function() {
                                        return newData.title;
                                    }
                                }
                            }
                        }
                    }
                }
            });
        }
    }
}

/**
 * يجلب بيانات الرسم الدائري من المسار التجريبي القديم عند استدعائه صراحة.
 * @param {Object} chart نسخة الرسم.
 * @returns {void} لا يستدعى تلقائيًا عند تحميل الصفحة.
 */
export function fetchDataAndUpdateChart(chart) {

    fetch('/api/delivery-exceptions')
        .then(response => response.json())
        .then(data => {
            updatecircleChart(chart, data);
        })
        .catch(error => {
            console.error('Error fetching delivery exceptions data:', error);
        });
}
