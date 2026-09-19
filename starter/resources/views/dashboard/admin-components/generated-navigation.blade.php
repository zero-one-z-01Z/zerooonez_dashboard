{{-- تقرأ التعريفات التي أنشأها المولّد؛ ظهور الرابط يتبع نفس صلاحية المسار. --}}
@if(admin()->check())
    @include('dashboard.admin-components.generated-links', ['generatedGroup' => null])
    @foreach(app(\App\Dashboard\Services\Dashboard\ResourceRegistry::class)->newGroups() as $generatedGroup => $generatedSidebar)
        <li class="menu-item {{ app(\App\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive($generatedGroup) ? 'active open' : '' }}">
            <a href="javascript:void(0);" class="menu-link menu-toggle">
                <i class="menu-icon {{ $generatedSidebar['icon'] }}"></i>
                <div>{{ __('admin.'.$generatedGroup) }}</div>
            </a>
            <ul class="menu-sub">@include('dashboard.admin-components.generated-links', ['generatedGroup' => $generatedGroup])</ul>
        </li>
    @endforeach
    @if(app()->environment('local') && config('dashboard.builder_enabled') && in_array(request()->server('REMOTE_ADDR'), ['127.0.0.1', '::1'], true))
        <li class="menu-item">
            <a href="{{ route('admin.dashboard-builder.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-code"></i><div>إنشاء صفحة داشبورد</div>
            </a>
        </li>
    @endif
@endif
