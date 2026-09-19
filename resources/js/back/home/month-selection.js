/** اختيار الشهر وجلب بيانات الرسم السنوي. */

/**
 * يربط عناصر قائمة الشهر بزر العرض ويرسل الشهر المختار إلى إحصاءات الشحن.
 * @param {Object} chart نسخة ApexCharts المراد تحديثها.
 * @returns {void} يربط نقر عناصر قائمة الشهر؛ طلب الإحصاءات يحدث عند الاختيار فقط.
 */
export function setupMonthSelection(chart) {

    var monthItems = document.querySelectorAll('.dropdown-menu .dropdown-item');
    var monthButton = document.querySelector('.btn-label-primary:not(.dropdown-toggle-split)');

    monthItems.forEach(function(item) {
        item.addEventListener('click', function(e) {

            e.preventDefault();

            var selectedMonth = this.innerText || this.textContent;

            if(selectedMonth === 'english') {
                selectedMonth = 'January';
            }
            selectedMonth = selectedMonth.trim();

            if (monthButton) {
                monthButton.innerText = selectedMonth;

                monthButton.textContent = selectedMonth;

                fetchMonthDataFromLaravel(selectedMonth, chart);
            }
        });
    });
}

/**
 * يجلب بيانات الشهر ويحدّث فئات الرسم والسلاسل والعنوان الفرعي عند نجاح الطلب.
 * @param {string} month اسم الشهر كما تعرضه القائمة.
 * @param {Object} chart نسخة الرسم.
 * @returns {void} ينفذ طلب POST للمسار القديم /api/shipment-statistics ويسجل الخطأ في console.
 */
export function fetchMonthDataFromLaravel(month, chart) {

    chart.updateOptions({
        chart: {
            animations: {
                dynamicAnimation: {
                    enabled: true
                }
            }
        }
    });

    fetch('/api/shipment-statistics', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify({
            month: month
        })
    })
        .then(response => {

            if (!response.ok) {

                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {

            chart.updateOptions({
                xaxis: {
                    categories: data.categories
                }
            });

            chart.updateSeries([
                {
                    name: "Shipment",
                    type: "column",
                    data: data.shipment
                },
                {
                    name: "Delivery",
                    type: "line",
                    data: data.delivery
                }
            ]);

            if (data.total_deliveries) {
                document.querySelector('.card-subtitle').textContent = 'Total number of deliveries ' + data.total_deliveries;
            }
        })
        .catch(error => {

            console.error('Error fetching shipment data:', error);

        });
}
