<?php

namespace App\Dashboard\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Dashboard\Http\Traits\AjaxResponseTrait;
use App\Dashboard\Services\Dashboard\ResourceDefinition;
use App\Dashboard\Services\Dashboard\ResourcePage;
use App\Dashboard\Services\Dashboard\ResourceRecord;
use App\Dashboard\Services\Dashboard\ResourceTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/** نقطة البداية للصفحات الجديدة: تعريف صغير، وعمليات مشتركة قابلة للتخصيص بالوراثة. */
abstract class GeneratedResourceController extends Controller
{
    use AjaxResponseTrait;

    public static function routeCapabilities(): array
    {
        return ['create' => true, 'update' => true, 'delete' => true, 'delete_all' => true];
    }

    private array $loadedDefinitions = [];

    /**
     * يعيد وصف المورد الذي تعتمد عليه كل عمليات الصفحة؛ تنفذه فئة الكنترولر المولدة.
     *
     * @return array<string, mixed> model وresource وpermission وtitle وخيارات التواريخ والحقول المطبعة.
     */
    abstract protected function definition(): array;

    /**
     * يقرأ JSON من dashboard/resources ويطبعه، ثم يحتفظ به داخل مثيل الكنترولر خلال الطلب.
     *
     * تعديل العناوين والظهور وقواعد الحقول يظهر في الطلب التالي. إضافة أعمدة أو تغيير
     * أنواعها يحتاج أيضا تحديث fillable وcasts وترحيل بنية الجدول، ولا تنفذه هذه القراءة.
     *
     * @param string $resource اسم المورد المثبت في الكنترولر المولد، مثل demo_products.
     * @return array<string, mixed> التعريف المطبّع؛ لا يقرأ سجلات الموديل أو يكتب ملفات.
     * @throws \JsonException عند وجود JSON تالف.
     * @throws \Illuminate\Validation\ValidationException عند مخالفة التعريف للعقد.
     */
    protected function loadDefinition(string $resource): array
    {
        return $this->loadedDefinitions[$resource] ??= app(ResourceDefinition::class)->normalize(json_decode(
            file_get_contents(base_path(trim((string) config('dashboard.generator.definition_path'), '/').'/'.$resource.'.json')),
            true, 64, JSON_THROW_ON_ERROR
        ));
    }

    /**
     * يبدأ استعلام App\Models\<model>؛ هذه نقطة تخصيص نطاق السجلات في كل عمليات CRUD.
     *
     * يمكن لفئة المورد استدعاء parent::model() ثم إضافة where أو with بحسب احتياجها.
     *
     * @return Builder استعلام جديد قبل تنفيذه؛ يشمل global scopes الخاصة بالموديل.
     */
    protected function model(): Builder
    {
        $model = rtrim((string) config('dashboard.generator.model_namespace', 'App\\Models'), '\\').'\\'.$this->definition()['model'];
        return $model::query();
    }

    /**
     * يرسم table_view من ResourcePage؛ يجهّز الحقول والروابط دون جلب صفوف الجدول.
     *
     * @return \Illuminate\Contracts\View\View صفحة الجدول والنماذج؛ الصفوف تطلب لاحقا من get_datatable.
     */
    public function index()
    {
        return view(config('dashboard.view_namespace', 'zerooonez-dashboard').'::screen.table_view', ['data' => app(ResourcePage::class)->data($this->definition())]);
    }

    /**
     * ينفذ بحث وترتيب وترقيم المورد عبر ResourceTable باستخدام استعلام model() الحالي.
     *
     * @param Request $request طلب DataTables وفيه draw وstart وlength والبحث والمرشحات والترتيب.
     * @return \Illuminate\Http\JsonResponse draw وrecordsTotal وrecordsFiltered وdata؛ دون غلاف Success.
     */
    public function get_datatable(Request $request)
    {
        return response()->json(app(ResourceTable::class)->response($request, $this->model(), $this->definition()));
    }

    /**
     * يجلب سجل التعديل ويعرض حقوله المعرّفة فقط؛ السجل الغائب يعيد 404.
     *
     * @param int|string $id معرف السجل من المسار، داخل نطاق model().
     * @return \Illuminate\Http\JsonResponse غلاف Success وفي data قيم النموذج التي يجهزها recordData.
     */
    public function get_single_item($id)
    {
        return $this->successResponse($this->recordData($this->model()->findOrFail($id)));
    }

    /**
     * يحفظ سجلا جديدا بمدخلات validatedData فقط، ثم يعيد القيم كما يقرأها الموديل.
     *
     * @param Request $request حقول نموذج الإضافة؛ لا تحفظ المفاتيح الإضافية خارج قواعد الخادم.
     * @return \Illuminate\Http\JsonResponse غلاف Success والسجل المسموح بعرضه؛ أخطاء التحقق تعيد 422.
     */
    public function store(Request $request)
    {
        $record = $this->model()->create($this->validatedData($request));
        return $this->successResponse($this->recordData($record), 'success');
    }

    /**
     * يحفظ تعديلات السجل الذي حدده المسار، ويطبق نفس تحقق الخادم المستخدم عند الإضافة.
     *
     * @param Request $request قيم الحقول الجديدة؛ id الموجود داخل النموذج لا يختار السجل المستهدف.
     * @param int|string $id معرف السجل داخل نطاق model()؛ الغائب يعيد 404.
     * @return \Illuminate\Http\JsonResponse غلاف Success وقيم السجل بعد التعديل.
     */
    public function update(Request $request, $id)
    {
        $record = $this->model()->findOrFail($id);
        $record->update($this->validatedData($request));
        return $this->successResponse($this->recordData($record), 'success');
    }

    /**
     * يحذف سجل المسار عبر Eloquent، فتعمل أحداث الموديل وSoftDeletes إن كان مفعلا.
     *
     * @param int|string $id معرف السجل داخل نطاق model()؛ الغائب يعيد 404.
     * @return \Illuminate\Http\JsonResponse غلاف Success بعد إتمام delete على الموديل.
     */
    public function delete($id)
    {
        $this->model()->findOrFail($id)->delete();
        return $this->successResponse('success', 'success');
    }

    /**
     * يتحقق من 1 إلى 500 معرف مختلف ثم يستدعي delete لكل سجل داخل معاملة قاعدة بيانات واحدة.
     *
     * فشل سجل يتراجع عن تغييرات قاعدة البيانات على اتصال الموديل؛ الآثار الخارجية
     * التي تضيفها في أحداث الموديل تحتاج آلية تنفيذ بعد نجاح المعاملة عند تخصيصها.
     *
     * @param Request $request مصفوفة ids من أعداد صحيحة غير مكررة.
     * @return \Illuminate\Http\JsonResponse غلاف Success والمعرفات المحذوفة عند نجاح المجموعة.
     */
    public function delete_all(Request $request)
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:500'], 'ids.*' => ['required', 'integer', 'distinct']]);
        $this->model()->getConnection()->transaction(function () use ($data) {
            foreach ($data['ids'] as $id) {
                $this->delete($id);
            }
        });
        return $this->successResponse($data['ids'], 'success');
    }

    /**
     * يعيد حتى عشرة اختيارات لكل صفحة، ويبحث في أول حقل text أو email أو في id عند غيابهما.
     *
     * @param Request $request page ابتداء من 1 وsearch الاختياري حتى 255 حرفا.
     * @return \Illuminate\Http\JsonResponse غلاف Success وفي data قائمة عناصر id وtext مرتبة بالمعرف.
     */
    public function get_list(Request $request)
    {
        $data = $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'search' => ['nullable', 'string', 'max:255']]);
        $fields = $this->definition()['fields'];
        $textFields = array_values(array_filter($fields, fn ($field) => in_array($field['type'], ['text', 'email'], true)));
        $label = $textFields[0]['name'] ?? 'id';
        $query = $this->model();
        if (($data['search'] ?? '') !== '') {
            $query->where($label, 'like', '%'.$data['search'].'%');
        }
        $records = $query->orderBy('id')->skip((($data['page'] ?? 1) - 1) * 10)->take(10)->get();
        return $this->successResponse($records->map(fn ($record) => ['id' => $record->id, 'text' => (string) $record->{$label}]), 'success');
    }

    /**
     * يبني قواعد Laravel من تعريف المورد ويعيد الحقول التي اجتازت التحقق فقط.
     *
     * يمكن لفئة المورد توسيع الدالة بعد parent::validatedData لإضافة تحويلات أو قواعد عمل؛
     * تعريف قواعد JavaScript وحده لا يغني عن هذا التحقق على الخادم.
     *
     * @param Request $request طلب الإضافة أو التعديل الجاري التحقق منه.
     * @return array<string, mixed> القيم المسموح بتمريرها إلى create أو update.
     * @throws \Illuminate\Validation\ValidationException عند عدم صلاحية أي حقل.
     */
    protected function validatedData(Request $request): array
    {
        return $request->validate(app(ResourceDefinition::class)->rules($this->definition()['fields']));
    }

    /**
     * يفوض عرض السجل إلى ResourceRecord دون تغيير hidden أو appends في الموديل العام.
     *
     * @param \Illuminate\Database\Eloquent\Model $record السجل المطلوب تجهيزه للجدول أو نموذج التعديل.
     * @return array<string, mixed> id وحقول definition فقط، مع casts/accessors وصيغة التاريخ المناسبة.
     */
    protected function recordData($record): array
    {
        return app(ResourceRecord::class)->data($record, $this->definition()['fields']);
    }
}
