/** منتقي نطاق تاريخ الحجز. */

/**
 * ينشئ منتقي نطاق الحجز إذا وجد العنصر ويستخدم نطاق الأشهر الستة السابق واللاحق.
 * @returns {void} يركب daterangepicker على bookingrange الموجودة؛ لا يرسل طلب بيانات.
 */
export function initializeBookingRange() {
    if ($('.bookingrange').length > 0) {
        var start = moment().subtract(6, 'months');
        var end = moment().add(6,'months');

        /**
         * يعرض بداية ونهاية النطاق المختار داخل زر الحجز.
         * @param {Object} start تاريخ البداية من Moment.
         * @param {Object} end تاريخ النهاية من Moment.
         * @returns {void} يغير النص الظاهر داخل زر النطاق عند وجود تاريخي البداية والنهاية.
         */
        function booking_range(start, end) {
            if(start&&end){
                $('.bookingrange span').html(start.format('M/D/YYYY') + ' - ' + end.format('M/D/YYYY'));
            }
        }

        $('.bookingrange').daterangepicker({
            startDate: start,
            endDate: end,
            ranges: {
                'Today': [moment(), moment()],
                'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                'Last 1 Month': [moment().subtract(1, 'months'), moment()],
                'Last 6 Months': [moment().subtract(6, 'months'), moment()],
                'This Year': [moment().startOf('year'), moment().endOf('year')]
            }
        }, booking_range);

    }

}
