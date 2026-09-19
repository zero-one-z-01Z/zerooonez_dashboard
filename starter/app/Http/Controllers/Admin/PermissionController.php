<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Dashboard\Http\Controllers\Concerns\BuildsDashboardPage;
use App\Dashboard\Http\Controllers\Concerns\HandlesDashboardTable;
use App\Http\Traits\AjaxResponseTrait;
use App\Http\Traits\ImageTrait;
use App\Http\Traits\NotificationTrait;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermissionController extends Controller
{
    use BuildsDashboardPage, HandlesDashboardTable;
    use AjaxResponseTrait,ImageTrait,NotificationTrait;
    public $modelName = 'permissions';
    public $permissions = ['create_permission', 'update_permission', 'delete_permission', 'view_permission'];
    public $datatable_url = 'admin.permissions.datatable';
    public $delete_all_url = 'admin.permissions.delete_all';
    public $get_single_item = 'admin.permissions.get_single_item';
    public $update_url = 'admin.permissions.update';
    public $delete_url = 'admin.permissions.delete';
    public $store_url = 'admin.permissions.store';
    public $daterange_filter = false;
    public $select2 = false;
    public $datatable = true;
    public $have_actions = true;
    public $have_check_box = false;
    public $have_validation = true;
    public $have_export = false;
    public $have_add = true;
    public $have_delete_all = false;
    public $daterannge_filter_name = 'created_at';
    public $daterange_filter_tooltip = '';
    public $filter_display = 'modal'; // modal - float
    public $show_columns = false;

    public $columns = ['id','name_ar','name_en',];

    /**
     * يبدأ استعلام Eloquent لموديل Role الذي يدير الأدوار والصلاحيات، دون تنفيذ القراءة.
     *
     * @return \Illuminate\Database\Eloquent\Builder الاستعلام القابل لإضافة شروط أخرى.
     */
    public function model(): \Illuminate\Database\Eloquent\Builder
    {
        return Role::query();
    }

    /**
     * يعرف مجموعات الموارد وأفعال view وcreate وupdate وdelete المستخدمة لبناء حقول صلاحيات الدور.
     * يلحق الموارد المولدة من سجل JSON مع الاحتفاظ بترتيب المجموعات القديمة ومنع تكرار معرفاتها.
     *
     * اسم checkbox يجمع الفعل وid، مثل view_demo_product؛ label الاختياري يعرض عنوان
     * المورد الجديد بينما name يظل مفتاح الترجمة للمجموعات القديمة. هذه الدالة لا تمنح صلاحيات.
     *
     * @return array<int, array> مجموعات id وname وpermissions مع label صريح للموارد المولدة.
     */
    public function permissionList()
    {
        $permissions[] = [
            'id' => 'dashboard_home',
            'name' => 'dashboard_home',
            'permissions' => [
                'view' => 'view',
            ]
        ];
        $permissions[] = [
            'id' => 'city',
            'name' => 'cities',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'area',
            'name' => 'areas',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'banner',
            'name' => 'banners',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'brand',
            'name' => 'brands',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'service',
            'name' => 'services',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'wanted',
            'name' => 'wanted',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'coupon',
            'name' => 'coupons',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'faq',
            'name' => 'faqs',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'customer_service',
            'name' => 'customer_service',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'customer_service_fee',
            'name' => 'customer_service_fees',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'customer_service_employee',
            'name' => 'customer_service_employees',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'customer_service_employee_fee',
            'name' => 'customer_service_employee_fees',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'optional_phone',
            'name' => 'optional_phones',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'brand_model',
            'name' => 'brand_models',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'feature',
            'name' => 'features',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'car_option',
            'name' => 'car_options',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'highlight',
            'name' => 'highlights',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];

        $permissions[] = [
            'id' => 'how_know_us',
            'name' => 'how_know_us',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];

        $permissions[] = [
            'id' => 'insurance',
            'name' => 'insurance',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'insurance_package',
            'name' => 'insurance_packages',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'transportation',
            'name' => 'transportations',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'time',
            'name' => 'times',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'part',
            'name' => 'parts',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'company',
            'name' => 'companies',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'scanner',
            'name' => 'scanners',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'auction',
            'name' => 'auctions',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'bill',
            'name' => 'bills',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];

        $permissions[] = [
            'id' => 'notification',
            'name' => 'notifications',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'settings',
            'name' => 'settings',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'permission',
            'name' => 'permissions',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'ticket_category',
            'name' => 'ticket_categories',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'ticket',
            'name' => 'tickets',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'email_type',
            'name' => 'email_types',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'admin',
            'name' => 'admins',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];
        $permissions[] = [
            'id' => 'user',
            'name' => 'users',
            'permissions' => [
                'view' => 'view',
                'create' => 'create',
                'update' => 'update',
                'delete' => 'delete',
            ]
        ];

        return app(\App\Dashboard\Services\Dashboard\ResourceRegistry::class)->permissionGroups($permissions);
    }

    /**
     * يعيد تعريف بطاقات إحصاءات الأدوار والصلاحيات؛ القائمة الحالية فارغة فلا تظهر بطاقات إضافية.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function statistics()
    {
        $data = [];
        return $data;
    }

    /**
     * يعرف أزرار صف الأدوار والصلاحيات وروابطها وأنواع التفاعل التي يستخدمها DataTables.
     * مفاتيح صلاحيات الأزرار: update_permission, delete_permission.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function datatable_actions()
    {
        $data = [];
        $data[] = ['name'=>__('buttons.edit'),'type'=>'modal','link'=>'#edit_modal','icon'=>'icon-base ti tabler-edit','onclick'=>'edit_item','permission'=>'update_permission'];
        $data[] = ['name'=>__('buttons.delete'),'type'=>'delete','link'=>route($this->delete_url,'#placeholder#'),
            'value'=>'id','icon'=>'icon-base ti tabler-trash','onclick'=>'delete_item','permission'=>'delete_permission'];
        // $data[] = ['name'=>'view','type'=>'link','link'=>'','icon'=>'icon-base ti tabler-eye','value'=>'id','blank'=>true,'permission'=>'users.view'];
        return $data;
    }

    /**
     * يعيد قائمة مرشحات واجهة فارغة لشاشة الأدوار والصلاحيات؛ يظل البحث العام متاحا بحسب إعدادات الجدول.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function filters()
    {
        $data = [];
        return $data;
    }

    /**
     * يحدد ترتيب خلايا جدول الأدوار والصلاحيات وعناوينها وأنواع عرضها ومسارات العلاقات.
     * مفاتيح العرض: id, name_ar, name_en.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function show_list()
    {
        $body[] = [
            "key"=>"id",
            "type"=>"id",
            "title"=>__('inputs.id'),
        ];
        $body[] = [
            "key"=>"name_ar",
            "title"=>__('inputs.name_ar'),
            "type"=>"text",
        ];

        $body[] = [
            "key"=>"name_en",
            "title"=>__('inputs.name_en'),
            "type"=>"text",
        ];
        return $body;
    }

    /**
     * يعرف حقول إضافة الأدوار والصلاحيات وأنواع المدخلات والتحقق المستخدم في الواجهة.
     * الحقول: name_ar, name_en, permissions.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function inputs_list()
    {
        $inputs = [];

        $inputs[] = ["id"=>"name_ar","input"=>"text","label"=>__('inputs.name_ar'),'value'=>"",'type'=>"text",'validation'=>[
            'required'=>true,
        ]];
        $inputs[] = ["id"=>"name_en","input"=>"text","label"=>__('inputs.name_en'),'value'=>"",'type'=>"text",'validation'=>[
            'required'=>true,
        ]];

        $inputs[] = ["id"=>"permissions","input"=>"permissions","label"=>__('inputs.permissions'),'value'=>"",'type'=>"permissions",
            'items'=>$this->permissionList()];

        return $inputs;
    }

    /**
     * يعرف حقول تعديل الأدوار والصلاحيات وأنواع المدخلات والتحقق المستخدم في الواجهة.
     * يعيد استخدام تعريف الإضافة ويضيف id مخفيا لأن بقية حقول النموذجين متطابقة.
     * الحقول: id, name_ar, name_en, permissions.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function update_inputs_list()
    {
        return array_merge([['id' => 'id', 'input' => 'hidden']], $this->inputs_list());
    }

    /**
     * يجهز أعمدة استعلام الأدوار والصلاحيات والعلاقات المطلوبة لعرض صفوف الجدول دون جلب النتائج بعد.
     *
     * @return \Illuminate\Database\Eloquent\Builder الاستعلام القابل لإضافة شروط أخرى.
     */
    private function initializeDataTableQuery()
    {
        $columns = $this->columns;
        return $this->model()
            ->select($columns);
    }

    /**
     * يجمع إعدادات شاشة الأدوار والصلاحيات مع الأقسام وروابط الإجراءات المشتركة لاستخدامها في الصفحة أو تبويبات التفاصيل.
     *
     * @return array التعريف أو القيم الموضحة أعلاه.
     */
    public function prepareData()
    {
        $columns = $this->columns;
        $sections = $this->dashboardPageSections();
        $data = $this->dashboardPageData($sections, [
            'columns' => $columns,
        ]);
        return $data;
    }

    /**
     * يجمع إعدادات شاشة الأدوار والصلاحيات مع الأقسام وروابط الإجراءات المشتركة ثم يعرض table_view.
     *
     * @return \Illuminate\Contracts\View\View قالب العرض وبياناته.
     */
    public function index()
    {
        $data = $this->prepareData();
        $permissions = Permission::pluck('key_name')->toArray();
        return view('dashboard.screen.table_view',compact('data','permissions'));
    }

    /**
     * يعيد صفوف الأدوار والصلاحيات وأعدادها بصيغة DataTables عبر مسار التصفية والترتيب المشترك.
     *
     * @param Request $request طلب DataTables: draw وstart وlength وfilters والترتيب.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_datatable(Request $request)
    {
        $filterableColumns = $this->columns;

        return response()->json(
            $this->dashboardTableData($request, $filterableColumns)
        );
    }

    /**
     * يعيد قائمة اختيار الأدوار والصلاحيات في صورة id وtext مع البحث والترقيم الحاليين.
     * يبحث في: name_ar, name_en؛ وحجم الصفحة عشر نتائج.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: search, page.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_list(Request $request)
    {
        $search = $request->input('search');
        $page = $request->input('page');
        $skip = ($page -1)*10;

        $data = $this->model()->where(function ($q) use ($search) {
            $q->where('name_ar', 'like', "%{$search}%")->orWhere('name_en', 'like', "%{$search}%");
            })
            ->select('id', 'name_ar','name_en')
            ->skip($skip)
            ->take(10)
            ->get();

        $results = $data->map(function ($item)  {
            return [
                'id' => $item->id,
                'text' => $item->getTranslation('name'),
            ];
        });

        return $this->successResponse($results, 'success', '200');
    }

    /**
     * يجلب سجل الأدوار والصلاحيات بالمعرف ويجهز بياناته لتعبئة نموذج التعديل.
     * يكشف الحقول المخفية كما يتطلب عقد التعديل الحالي؛ شكل الاستجابة لم يتغير.
     * يضيف حالة كل مفتاح صلاحية لتمكين تعبئة مربعات الاختيار.
     *
     * @param int|string $id معرف السجل المطلوب في قاعدة البيانات.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function get_single_item($id)
    {
        $data = $this->model()->with('permissions')->find($id);
        $data->makeVisible($data->getHidden());
        return $this->successResponse($data, 'success', '200');
    }

    /**
     * ينشئ الدور من الاسمين ثم ينشئ مفاتيح الصلاحيات المختارة ويربطها بالدور عبر RolePermission.
     *
     * @param Request $request طلب العملية بالحقول التي يعالجها هذا المسار.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function store(Request $request)
    {
        $names = $request->validate(['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255']]);
        $keys = $this->selectedPermissionKeys($request);
        $role = DB::transaction(function () use ($names, $keys) {
            $role = Role::query()->create($names);
            $role->permissions()->sync(collect($keys)->map(fn (string $key) => Permission::query()->firstOrCreate(['key_name' => $key])->id));
            return $role;
        });
        return $this->successResponse($role, 'success', '200');
    }

    /**
     * يحدث اسمي الدور ويحذف روابط صلاحياته القديمة ثم يعيد بناءها من الاختيارات المرسلة.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: id.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function update(Request $request)
    {
        $role = $this->model()->findOrFail($request->integer('id'));
        $names = $request->validate(['name_ar' => ['required', 'string', 'max:255'], 'name_en' => ['required', 'string', 'max:255']]);
        $keys = $this->selectedPermissionKeys($request);
        DB::transaction(function () use ($role, $names, $keys) { $role->update($names); $role->permissions()->sync(collect($keys)->map(fn (string $key) => Permission::query()->firstOrCreate(['key_name' => $key])->id)); });
        return $this->successResponse($role->fresh('permissions'), 'success', '200');
    }

    private function selectedPermissionKeys(Request $request): array
    {
        $allowed = collect($this->permissionList())->flatMap(fn (array $group) => collect($group['permissions'])->map(fn (string $action) => $action.'_'.$group['id']))->all();
        $selected = collect($request->all())->filter(fn ($value, $key) => in_array($key, $allowed, true) && filter_var($value, FILTER_VALIDATE_BOOLEAN))->keys()->values()->all();
        if (! admin_user()?->super) $selected = array_values(array_intersect($selected, admin_user()?->all_permissions() ?? []));
        return $selected;
    }

    /**
     * يحذف سجل الأدوار والصلاحيات المحدد بالمعرف عبر Eloquent ثم يعيد استجابة نجاح.
     *
     * @param int|string $id معرف السجل المطلوب في قاعدة البيانات.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function delete($id)
    {
        try {
            $this->model()->findOrFail($id)->delete();
        } catch (\LogicException $exception) {
            return $this->errorResponse($exception->getMessage(), 422);
        }
        return $this->successResponse('success', 'success', '200');
    }

    /**
     * يمر على ids ويحذف سجلات الأدوار والصلاحيات باستدعاء delete لكل معرف للحفاظ على مسار الحذف الفردي.
     * يعيد قائمة المعرفات؛ يحتفظ السلوك الحالي بعدم تجميع استجابات كل حذف.
     *
     * @param Request $request الطلب؛ الحقول المستخدمة مباشرة: ids.
     * @return \Illuminate\Http\JsonResponse استجابة الواجهة وفق عقد الدالة الحالي.
     */
    public function delete_all(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $this->delete($id);
        }
        return $this->successResponse($ids, 'success', '200');
    }
}
