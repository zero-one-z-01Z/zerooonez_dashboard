/** اتجاه العرض وتنسيق تواريخ رسائل المحادثة. */

/**
 * يعيد اتجاه واجهة الحقول حسب currentLocale التي توفرها Blade.
 * @returns {"rtl"|"ltr"} اتجاه المحتوى.
 */
export function direction() {
    return currentLocale == 'ar' ? 'rtl' : 'ltr';
}

/**
 * ينسق التاريخ بتوقيت المتصفح إلى سنة وشهر ويوم وساعة بنظام AM/PM.
 * @param {string} dateString تاريخ يقبله Date.
 * @returns {string} تاريخ العرض؛ الاسم القديم محفوظ رغم أنه لا يحسب الزمن النسبي.
 */
export function formatDateAgo(dateString) {

    var date = new Date(dateString);

    var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

    var year = date.getFullYear();
    var month = monthNames[date.getMonth()];
    var day = date.getDate();
    var hours = date.getHours();
    var minutes = date.getMinutes();

    var ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // Handle midnight (0 hours)

    minutes = minutes < 10 ? '0' + minutes : minutes;

    var formattedDate = year + ' ' + month + ' ' + day + ' ' + hours + ':' + minutes + ' ' + ampm;

    return formattedDate;
}
