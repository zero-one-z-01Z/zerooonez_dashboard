# رسوم الصفحة الرئيسية

تستمر Blade في تحميل `../home.js`. عند `DOMContentLoaded` ينشئ المدخل الرسم السنوي ثم الدائري ثم متوسط المبيعات.

| الملف | المسؤولية |
| --- | --- |
| `year-chart-options.js` | `buildYearChartOptions(theme, data)` يبني خيارات السنتين والأنماط من بيانات الخادم دون الوصول إلى DOM. |
| `year-chart.js` | `initYearStatisticsChart()` يركب الرسم على `#yearStatisticsChart` ويربط اختيار الشهر. |
| `month-selection.js` | `setupMonthSelection(chart)` يربط قائمة الشهر، و`fetchMonthDataFromLaravel(month, chart)` يرسل الطلب ويحدّث الرسم. |
| `circle-chart.js` | `initcircleChart` و`updatecircleChart` ينشئان الرسم الدائري ويحدثانه. `fetchDataAndUpdateChart` طلب اختياري لا يعمل تلقائيًا. |
| `daily-sales-chart.js` | إنشاء وتحديث الرسم المصغر عبر `initAverageDailySalesChart` و`updateAverageDailySalesChart`؛ الجلب الاختياري عبر `fetchDailySalesDataAndUpdateChart`. |

تضع Blade بيانات الرسوم داخل عنصر JSON باسم `dashboard-home-config`. يقرأ `home.js` منه `year` و`circleChart` و`averageDailySales`، ثم يمررها للوحدات دون إنشاء متغيرات ديناميكية جديدة على `window`. الألوان والخط من `config` الذي يوفره القالب، و`ApexCharts` من مكتبة الصفحة.

لا تُسجَّل أي دالة جديدة على `window`؛ الرسوم تستخدم استيرادات ES Modules داخل مدخلها. يمكن استيراد دوال الإنشاء والتحديث من هذه الوحدات في صفحة أخرى.

العقود السابقة محفوظة: اختيار الشهر يربط محدد القائمة العام `.dropdown-menu .dropdown-item` ويرسل `POST /api/shipment-statistics`. طلبا `/api/delivery-exceptions` و`/api/average-daily-sales` اختياريان ولا يجريان عند التحميل. هذه المسارات تحتاج تنفيذًا فعليًا في المشروع قبل الاعتماد عليها.

التفاصيل الخاصة بكل دالة وformatter موجودة بالعربية داخل الملفات، مع [مرجع JavaScript الكامل](../../../../docs/dashboard/javascript-reference.md). اختبارات `tests/JavaScript/forms-charts.test.js` تتحقق من خيارات الرسوم وعقود تحديثها؛ فحص الرسم الفعلي يحتاج مكتبة ApexCharts وبيانات Blade في المتصفح.
