<!-- User -->
@php
    if(admin()->check()){
        $user = admin_user();
        $active_route = route('admin.admins.update_active');
        $logout_route = route('admin.logout');
        $image = asset('logo.svg');
    }else if(company()->check()){
        $user = company_user();
        $active_route = route('company.companies.update_active');
        $logout_route = route('company.logout');
        $image = $user->logo;
    }else if(customer_service()->check()){
        $user = customer_service_user();
        $active_route = route('customer_service.customer_services.update_active');
        $logout_route = route('customer_service.logout');
        $image = $user->image;
    } else if(customer_service_employee()->check()){
        $user = customer_service_employee_user();
        $logout_route = route('customer_service_employee.logout');
        $image = asset('logo.svg');
    }
@endphp
<li class="nav-item navbar-dropdown dropdown-user dropdown">
    <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
        <div class="avatar avatar-online">
            <img src="{{$image}}" alt="" class="rounded-circle">
        </div>
    </a>

    <ul class="dropdown-menu dropdown-menu-end">
        <li>
            <a class="dropdown-item mt-0" href="">
                <div class="d-flex align-items-center">
                    <div class="flex-shrink-0 me-2">
                        <div class="avatar avatar-online">
                            <img src="{{$image}}" alt="" class="rounded-circle">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{$user->name}}</h6>
{{--                        <small class="text-body-secondary">Admin</small>--}}
                    </div>
                </div>
            </a>
        </li>

       @isset($active_route)
            <li>
                <div class="d-flex align-items-center px-2">
                    <span>{{__('admin.active')}}:</span>
                    <label class="switch switch-primary switch-sm mb-0 me-8 ms-auto">
                        <input type="checkbox" class="switch-input custom"
                               id="admin_active"
                               data-id="{{ $user->id }}"
                               data-link="{{ $active_route }}"
                            {{ $user->active === 1 ? 'checked' : '' }}>
                        <span class="switch-toggle-slider">
        <span class="switch-{{ $user->active === 1 ? 'on' : 'off' }}"></span>
      </span>
                    </label>
                </div>
            </li>
        @endisset
        <li>
            <div class="d-grid px-2 pt-2 pb-1">
                <button
                    type="button"
                    class="btn btn-sm btn-primary d-flex"
                    data-bs-toggle="modal"
                    data-bs-target="#update_password_form_modal">
                    <small class="align-middle">{{ __('admin.change_password') }}</small>
                </button>
            </div>
        </li>

        <li>

            <div class="d-grid px-2 pt-2 pb-1">
                @if(admin()->check())
                    <form method="POST" action="{{$logout_route}}">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger d-flex">
                            <small class="align-middle">{{__('buttons.logout')}}</small>
                            <i class="icon-base ti tabler-logout ms-2 icon-14px"></i>
                        </button>
                    </form>
                @else
                    <a class="btn btn-sm btn-danger d-flex" href="{{$logout_route}}" >
                        <small class="align-middle">{{__('buttons.logout')}}</small>
                        <i class="icon-base ti tabler-logout ms-2 icon-14px"></i>
                    </a>
                @endif
            </div>
        </li>
    </ul>
</li>
<!--/ User -->
