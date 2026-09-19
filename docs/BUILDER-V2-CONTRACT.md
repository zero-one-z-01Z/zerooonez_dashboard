# عقد تنفيذ Builder v2

هذا الملف عقد مشترك للواجهة والخادم؛ v1 يبقى متوافقًا مع مولّده الحالي. v2 مسار جديد داخل نفس الأداة، وليس ترحيلًا للصفحات القديمة.

## التعريف

الجذر: `schema_version:2`, `model`, `controller` (اسم كلاس فقط وينتهي بـController), `resource`, `permission`, `title_key`, `title_ar`, `title_en`, `create_model`, `timestamps`, `page_type:table|custom`.

`capabilities`: booleans `create,update,delete,delete_all,export,filters,show` (view ضمنية). `fields` قائمة الحقول، `edit_mode:same|custom`, `edit_fields` عند custom. `columns`, `filters`, `actions`, `modals` قوائم مستقلة. `sidebar`: `mode:root|sub|hidden`, `group` (معرف ثابت), `group_ar`, `group_en`, `icon`, `order`. `notes` نص قواعد العمل الخاصة.

الحقل: `name` (أو id للاستيراد), `column` (افتراضي name), `input` من أنواع renderer الستة عشر، `type` نوع التخزين القديم text/textarea/email/integer/decimal/boolean/date/datetime أو json للمتخصص، `html_type`, `label_key`, `label_ar`, `label_en`, `language:neutral|ar|en|both`, `validation:{required,min,max}`, `width`, `default`, `read_only`, `in_table`, `filterable`. `empty` استثناء بلا name/column.

`items:[{value,text_ar,text_en,selected}]`. `select2` boolean و`route` اسم route لا URL حر. `relation:{name,model,foreign_key,owner_key,value_name,key_name,table}`، مع `parent_id,child_id,parent_key,child_key` على الحقل. `storage:{strategy:scalar|belongsTo|pivot|json|children|media|custom,relation,disk,directory,deleted_key}`. `max_files,max_file_size,accepted_files,min_rows,max_rows,inputs` للمتخصص والمتكرر. `read_path` مسار القراءة المشتق. metadata المتخصصة تبقى في الحزمة؛ لا تدعي تنفيذًا تلقائيًا.

الأعمدة: `{key,label_key,label_ar,label_en,type,searchable,sortable}`. الفلاتر: تعريف حقل مع `operator:equals|contains`, `target`, `audience:admin|all`. الأكشنات: `{name_key,name_ar,name_en,type:link|modal|delete|custom_modal,route,link,form_id,icon,value,blank,permission,onclick,if:[{key,value}],operation}`. المودالات: `{id,form_id,title_key,title_ar,title_en,route,method,permission,operation,inputs}`.

## واجهة الخدمات المتفق عليها

- `ResourceDefinition::normalize` يترك v1 كما هو، ويفوض v2 إلى `ResourceDefinitionV2::normalize`؛ normalized v2 يحتفظ schema_version=2 وبالمفاتيح أعلاه؛ حقول both تُوسّع مرة واحدة وlanguage يصبح ar/en.
- `ResourceDefinitionV2::reasons(array $definition): array` يعيد أسباب الحاجة إلى Agent (أسباب بنيوية، بلا I/O). `permissions(array $definition): array` قائمة مفاتيح الصلاحيات كاملة. `rules(array $fields): array` للأنواع المباشرة.
- `ResourcePageV2::data(array $definition): array` و`ResourceRecordV2::data($record,array $definition): array` و`ResourceTableV2::response(Request,Builder,array)` للعرض المباشر. يضاف controller v2 مستقل كي تبقى عقود v1.
- `ScaffoldGenerator::preview` يفوض v2 إلى `ScaffoldGeneratorV2`; النتيجة تبقي `definition,files,fingerprint,next_steps` وتضيف `mode:direct|agent_required`, `reasons`, `changes:[{path,operation,before,after,diff}]`, `summary`, `handoff:{json,markdown}`, `form_preview:{create,edit}`. agent_required لا يولد كودًا جزئيًا ولا يسمح generate.
- `generate` نفس الطلب الحالي مع fingerprint؛ كتابة ملفات جديدة وتعديلات مشتركة محددة بالمعاينة فقط.
- `GET /admin/dashboard-builder/catalog?model=Name` يعيد `{models:[string],routes:[string],groups:[{id,label}],model:{name,table,columns,fillable,casts,relations,warnings}}`؛ metadata فقط. لا أسماء حقول محمية/بيانات سجلات أو أسرار.
- الترجمات v2 في ملفات lang الحالية. العرض يستطيع استخدام labels ثنائية كـfallback للمعاينة فقط؛ التوليد يكتب المفاتيح ويكشف التعارض.

## حدود تنفيذ النسخة

المباشر: scalar/text/textarea/switch/hidden/empty/single ثابت أو belongsTo بسيط. أي chain/multi_select/media/map/boundary/html/multiform/permissions/custom action/show/custom page أو قواعد أعمال يذهب بالكامل إلى Agent. كل الأنواع قابلة للإعداد والاستيراد والتصدير ومعاينة العقود. runtime القديم يبقى مدعومًا مع إصلاحات scoped للأسماء والـlifecycle؛ لا نضيف backend CRUD عام للملفات أو children من غير حزمة صفحة معتمدة.

معرف الراوت authoritative. العلاقات whitelist ونطاق Eloquent. لا request->all ولا makeVisible(all). الحذف الجماعي يستلزم delete. غياب array يحافظ عليها، والتفريغ صريح. لا migrations أو منح أدوار تلقائيًا.
