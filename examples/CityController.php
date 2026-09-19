<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use ZeroOneZ\Dashboard\Http\Controllers\Concerns\BuildsDashboardPage;
use ZeroOneZ\Dashboard\Http\Controllers\Concerns\HandlesDashboardTable;
use ZeroOneZ\Dashboard\Http\Traits\AjaxResponseTrait;

/**
 * Complete reference page. Copy it to the consuming application, then add:
 * Route::prefix('cities')->name('cities.')->controller(CityController::class)
 *     ->middleware('can:view_city')->group(fn () => all_routes('city'));
 */
final class CityController extends Controller
{
    use AjaxResponseTrait, BuildsDashboardPage, HandlesDashboardTable;

    public string $modelName = 'cities';
    public array $permissions = ['view_city', 'create_city', 'update_city', 'delete_city'];
    public string $datatable_url = 'admin.cities.datatable';
    public string $delete_all_url = 'admin.cities.delete_all';
    public string $get_single_item = 'admin.cities.get_single_item';
    public string $update_url = 'admin.cities.update';
    public string $delete_url = 'admin.cities.delete';
    public string $store_url = 'admin.cities.store';
    public bool $daterange_filter = false;
    public string $daterannge_filter_name = 'created_at';
    public string $daterange_filter_tooltip = '';
    public string $filter_display = 'modal';
    public bool $show_columns = false;
    public bool $select2 = false;
    public bool $datatable = true;
    public bool $have_actions = true;
    public bool $have_check_box = true;
    public bool $have_validation = true;
    public bool $have_export = true;
    public bool $have_add = true;
    public bool $have_delete_all = true;

    public function index()
    {
        return view('zerooonez-dashboard::screen.table_view', ['data' => $this->pageData()]);
    }

    public function get_data()
    {
        return $this->dashboardTabResponse($this->pageData());
    }

    public function get_datatable(Request $request)
    {
        return response()->json($this->dashboardTableData($request, ['id', 'name_ar', 'name_en']));
    }

    public function store(Request $request)
    {
        $city = City::query()->create($request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]));

        return $this->successResponse($city->only(['id', 'name_ar', 'name_en']), 'success');
    }

    public function update(Request $request, int $id)
    {
        $city = City::query()->findOrFail($id);
        $city->update($request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
        ]));

        return $this->successResponse($city->only(['id', 'name_ar', 'name_en']), 'success');
    }

    public function delete(int $id)
    {
        City::query()->findOrFail($id)->delete();

        return $this->successResponse('success', 'success');
    }

    public function delete_all(Request $request)
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer']])['ids'];
        City::query()->whereKey($ids)->delete();

        return $this->successResponse($ids, 'success');
    }

    public function get_single_item(int $id)
    {
        return $this->successResponse(City::query()->findOrFail($id)->only(['id', 'name_ar', 'name_en']));
    }

    public function show(int $id)
    {
        return $this->get_single_item($id);
    }

    public function get_list(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $localeColumn = app()->getLocale() === 'ar' ? 'name_ar' : 'name_en';
        $items = City::query()
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested
                ->where('name_ar', 'like', '%'.$search.'%')
                ->orWhere('name_en', 'like', '%'.$search.'%')))
            ->select(['id', 'name_ar', 'name_en'])
            ->forPage($page, 10)
            ->get()
            ->map(fn (City $city) => ['id' => $city->id, 'text' => $city->{$localeColumn}]);

        return $this->successResponse($items, 'success');
    }

    public function statistics(): array { return []; }
    public function filters(): array { return []; }

    public function show_list(): array
    {
        return [
            ['key' => 'id', 'type' => 'id', 'title' => __('inputs.id')],
            ['key' => 'name_ar', 'type' => 'text', 'title' => __('inputs.name_ar')],
            ['key' => 'name_en', 'type' => 'text', 'title' => __('inputs.name_en')],
        ];
    }

    public function inputs_list(): array
    {
        return [
            ['id' => 'name_ar', 'input' => 'text', 'type' => 'text', 'label' => __('inputs.name_ar'), 'value' => '', 'validation' => ['required' => true]],
            ['id' => 'name_en', 'input' => 'text', 'type' => 'text', 'label' => __('inputs.name_en'), 'value' => '', 'validation' => ['required' => true]],
        ];
    }

    public function update_inputs_list(): array
    {
        return [['id' => 'id', 'input' => 'hidden'], ...$this->inputs_list()];
    }

    public function datatable_actions(): array
    {
        return [
            ['name' => __('buttons.edit'), 'type' => 'modal', 'link' => '#edit_modal', 'icon' => 'icon-base ti tabler-edit', 'onclick' => 'edit_item', 'permission' => 'update_city'],
            ['name' => __('buttons.delete'), 'type' => 'delete', 'link' => route($this->delete_url, '#placeholder#'), 'value' => 'id', 'icon' => 'icon-base ti tabler-trash', 'onclick' => 'delete_item', 'permission' => 'delete_city'],
        ];
    }

    protected function initializeDataTableQuery(): Builder
    {
        return City::query()->select(['id', 'name_ar', 'name_en', 'created_at']);
    }

    private function pageData(): array
    {
        return $this->dashboardPageData($this->dashboardPageSections(), [
            'columns' => ['id', 'name_ar', 'name_en'],
        ]);
    }
}
