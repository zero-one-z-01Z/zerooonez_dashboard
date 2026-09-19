# JavaScript الداشبورد

الشرح الكامل في [مرجع JavaScript](../../../docs/dashboard/javascript-reference.md)، وشرح عمل المنظومة والتغييرات في [دليل التغييرات](../../../docs/dashboard/CHANGES-EXPLAINED.ar.md). كل دالة مسماة في الوحدات تحمل شرحًا عربيًا بجوارها للغرض والمدخلات والنتيجة أو أثرها على الصفحة.

| المجلد أو المدخل | دوره |
| --- | --- |
| [forms](forms/README.md) ← `crud.js` | تعبئة النماذج والتحقق والإرسال والأحداث والوسائط. |
| [tables](tables/README.md) ← `table.js` | ترجمة تعريف الشاشة إلى أعمدة وحقول وعمليات سجلات. |
| [datatables](datatables/README.md) ← `datatable-init.js` | محرك DataTables والفلاتر والإجراءات والأدوات والنوافذ. |
| `page-table.js` | يقرأ JSON تعريف الجدول في Blade ويبدأه بعد جاهزية DOM. |
| [item-tables](item-tables/README.md) ← `item-table.js` | تبديل جداول التفاصيل وتعريفاتها ونماذجها بأحدث استجابة AJAX. |
| [home](home/README.md) ← `home.js` | رسوم الصفحة الرئيسية وخياراتها وتحديثاتها. |
| [builder](builder/README.md) ← `builder.js` | واجهة تعريف المورد ومعاينة الملفات ثم طلب إنشائها من Laravel. |
| `core/` | `window.Dashboard` وقراءة JSON وتحميل المكتبات ودورة حياة المحتوى. |
| `pages/` | منطق الإعدادات والمحادثة وتسجيل الدخول المستخرج من Blade. |

ملفات الدخول تسجل واجهاتها داخل `window.Dashboard`، ثم ينشر `core/dashboard.js` الأسماء القديمة التي تتوقعها Blade كطبقة توافق. عدّل المسؤولية في ملفها الفرعي؛ لا تضف `<script>` لكل وحدة، ولا تنقل منطقها مجددًا إلى ملف الدخول. الجداول العادية والتفاصيل تستورد `table.js` عبر مدخليها الفعليين.

من جذر المشروع، `npm run test:dashboard` يشغل اختبارات العقود والمنطق، و`npm run build` يبني ملفات Vite. حدود اختبار المتصفح ونتائجه موضحة في [سجل التحقق](../../../docs/dashboard/verification.md).
