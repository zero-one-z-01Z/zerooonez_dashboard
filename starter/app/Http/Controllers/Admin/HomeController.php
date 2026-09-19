<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Permission;
use App\Models\Role;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        $numbers = [
            $this->card(Admin::query(), __('admin.admins'), 'admin.admins.index', 'primary', 'users'),
            $this->card(Role::query(), __('admin.roles'), 'admin.permissions.index', 'success', 'shield'),
            $this->card(Permission::query(), __('admin.permissions'), 'admin.permissions.index', 'info', 'key'),
        ];
        $avg = ['title' => __('admin.records_this_month'), 'value' => Admin::whereMonth('created_at', now()->month)->count(), 'steps' => $this->dailyCounts(Admin::query())];
        $sales = null;
        $total_earn = null; $circle_chart = null; $list_compare = null; $total_orders = Admin::count();
        $this_year_counts = $this->months(Admin::query(), now()->year); $previous_Year_counts = $this->months(Admin::query(), now()->subYear()->year);
        $progress = null;
        return view('dashboard.screen.home', compact('numbers', 'avg', 'sales', 'total_earn', 'circle_chart', 'list_compare', 'total_orders', 'this_year_counts', 'previous_Year_counts', 'progress'));
    }
    public function change_lang(string $lang) { $locale = in_array($lang, ['ar', 'en'], true) ? $lang : config('app.locale'); session(['locale' => $locale, 'lang' => $locale]); return redirect()->route('admin.home'); }
    private function card($query, string $title, string $route, string $class, string $icon): array { return ['title' => $title, 'value' => $query->count(), 'link' => route($route), 'class' => $class, 'icon' => $icon, 'percentage' => 0, 'growth' => '0%', 'description' => __('admin.records_this_month')]; }
    private function dailyCounts($query): array { return collect(range(1, now()->day))->map(fn ($day) => (clone $query)->whereDate('created_at', Carbon::now()->day($day))->count())->all(); }
    private function months($query, int $year): array { return collect(range(1, 12))->map(fn ($month) => (clone $query)->whereYear('created_at', $year)->whereMonth('created_at', $month)->count())->all(); }
}
