/** خيارات الرسم السنوي قابلة لإعادة الاستخدام والاختبار. */

/**
 * يبني خيارات الرسم السنوي من ألوان القالب وبيانات السنتين المرسلة من Blade.
 * @param {Object} [theme=config] ألوان وخط القالب.
 * @param {Object} [data=window] categories وthisYear وpreviousYear ومصفوفتا البيانات.
 * @returns {Object} إعدادات ApexCharts دون إنشاء عناصر DOM.
 */
export function buildYearChartOptions(theme = config, data = window) {
    var shipmentChartOptions = {
        series: [
            {
                name: data.thisYear,
                type: "column",
                data:  data.thisYearData
            },
            {
                name: data.previousYear,
                type: "line",
                data: data.previousYearData
            }
        ],
        chart: {
            height: 320,
            type: "line",
            stacked: !1,
            parentHeightOffset: 0,
            toolbar: {
                show: !1
            },
            zoom: {
                enabled: !1
            }
        },
        markers: {
            size: 5,
            colors: [theme.colors.white],
            strokeColors: theme.colors.primary,
            hover: {
                size: 6
            },
            borderRadius: 4
        },
        stroke: {
            curve: "smooth",
            width: [0, 3],
            lineCap: "round"
        },
        legend: {
            show: !0,
            position: "bottom",
            markers: {
                size: 4,
                offsetX: -3,
                strokeWidth: 0
            },
            height: 40,
            itemMargin: {
                horizontal: 10,
                vertical: 0
            },
            fontSize: "15px",
            fontFamily: theme.fontFamily,
            fontWeight: 400,
            labels: {
                colors: theme.colors.headingColor,
                useSeriesColors: !1
            },
            offsetY: 5
        },
        grid: {
            strokeDashArray: 8,
            borderColor: theme.colors.borderColor
        },
        colors: [theme.colors.warning, theme.colors.primary],
        fill: {
            opacity: [1, 1]
        },
        plotOptions: {
            bar: {
                columnWidth: "30%",
                startingShape: "rounded",
                endingShape: "rounded",
                borderRadius: 4
            }
        },
        dataLabels: {
            enabled: !1
        },
        xaxis: {
            tickAmount: 12,
            categories: data.categories,
            labels: {
                style: {
                    colors: theme.colors.textMuted,
                    fontSize: "13px",
                    fontFamily: theme.fontFamily,
                    fontWeight: 400
                }
            },
            axisBorder: {
                show: !1
            },
            axisTicks: {
                show: !1
            }
        },
        yaxis: {
            labels: {
                style: {
                    colors: theme.colors.textMuted,
                    fontSize: "13px",
                    fontFamily: theme.fontFamily,
                    fontWeight: 400
                },
                /**
                 * يمرر تسمية محور القيم دون تقريب أو تغيير.
                 * @param {number|string} e قيمة العلامة التي أرسلتها ApexCharts.
                 * @returns {number|string} القيمة نفسها، دون تعديل الرسم.
                 */
                formatter: function(e) {
                    return e
                }
            }
        },
        responsive: [
            {
                breakpoint: 1400,
                options: {
                    chart: {
                        height: 320
                    },
                    xaxis: {
                        labels: {
                            style: {
                                fontSize: "10px"
                            }
                        }
                    },
                    legend: {
                        fontSize: "13px"
                    }
                }
            },
            {
                breakpoint: 1025,
                options: {
                    chart: {
                        height: 415
                    },
                    plotOptions: {
                        bar: {
                            columnWidth: "50%"
                        }
                    }
                }
            },
            {
                breakpoint: 982,
                options: {
                    plotOptions: {
                        bar: {
                            columnWidth: "30%"
                        }
                    }
                }
            },
            {
                breakpoint: 480,
                options: {
                    chart: {
                        height: 250
                    },
                    legend: {
                        offsetY: 7
                    }
                }
            }
        ]
    };
    return shipmentChartOptions;
}
