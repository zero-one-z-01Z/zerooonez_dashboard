@foreach(app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->navigation($generatedGroup ?? null) as $generatedItem)
    <li class="menu-item {{ Route::is('admin.'.$generatedItem['resource'].'.*') ? 'active' : '' }}">
        <a href="{{ route('admin.'.$generatedItem['resource'].'.index') }}" class="menu-link">
            <i class="menu-icon {{ $generatedItem['sidebar']['icon'] ?? 'icon-base ti tabler-table' }}"></i>
            <div>{{ isset($generatedItem['title_key']) ? __('admin.'.preg_replace('/^admin\./', '', $generatedItem['title_key'])) : $generatedItem['title'] }}</div>
        </a>
    </li>
@endforeach
