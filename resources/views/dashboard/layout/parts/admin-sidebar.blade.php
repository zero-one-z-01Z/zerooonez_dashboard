<aside id="layout-menu" class="layout-menu menu-vertical menu">
    <!-- logo -->
    @php
    if(admin()->check()||customer_service_employee()->check()){
        $image = asset('logo.svg');
    }else if(company()->check()){
        $image = company_user()->logo;
    }else if(customer_service()->check()){
        $image = customer_service_user()->image;
    }

 @endphp
    <div class="app-brand demo">
        <a href="{{route('admin.home')}}" class="app-brand-link">
            <span class="app-brand-logo demo">
              <span class="text-primary">
                <img src="{{$image}}" style="width:50px; height:50px">
              </span>
            </span>
            <span class="app-brand-text demo menu-text fw-bold ms-3">{{env('APP_NAME')}}</span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
            <i class="icon-base ti tabler-x d-block d-xl-none"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1">
        <!-- Dashboards -->
        @if(admin()->check())
            <li class="menu-item {{Route::is(['admin.home']) ? 'active' : ''}} open">
                <a href="{{route('admin.home')}}" class="menu-link ">
                    <i class="menu-icon icon-base ti tabler-smart-home "></i>
                    <div >{{__('admin.home')}}</div>
                </a>
            </li>
        @endif

        <!-- Apps & Pages -->
        <li class="menu-header small">
            <span class="menu-header-text">{{__('admin.pages')}}</span>
        </li>
        @if(admin()->check())
                @if(\Illuminate\Support\Facades\Gate::any(['view_user','view_admin']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('users'))
                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('users') ? 'active open' : '' }} {{Route::is(['admin.users.index','admin.users.show','admin.admins.index'])? 'active' : ''}}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div>{{__('admin.users')}}</div>
                        </a>
                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'users'])
                            @can('view_user')
                                <li class="menu-item {{Route::is(['admin.users.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.users.index')}}" class="menu-link">
                                        <div>{{__('admin.users')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_admin')
                                <li class="menu-item {{Route::is(['admin.admins.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.admins.index')}}" class="menu-link">
                                        <div>{{__('admin.admins')}}</div>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                @if(\Illuminate\Support\Facades\Gate::any(['view_scanner','view_company','view_feature','view_car_option','view_transportation','view_time','view_part']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('scan'))
                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('scan') ? 'active open' : '' }} {{Route::is(['admin.scanners.index',
'admin.companies.index','admin.features.index','admin.car_options.index','admin.transportations.index',
'admin.times.index','admin.area_times.index','admin.parts.index'])? 'active' : ''}}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div>{{__('admin.scan')}}</div>
                        </a>
                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'scan'])

                            @can('view_company')
                                <li class="menu-item {{Route::is(['admin.companies.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.companies.index')}}" class="menu-link">
                                        <div>{{__('admin.companies')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_scanner')
                                <li class="menu-item {{Route::is(['admin.scanners.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.scanners.index')}}" class="menu-link">
                                        <div>{{__('admin.scanners')}}</div>
                                    </a>
                                </li>
                            @endcan
                                @can('view_feature')
                                    <li class="menu-item {{Route::is(['admin.features.index'])? 'active' : ''}}">
                                        <a href="{{route('admin.features.index')}}" class="menu-link">
                                            <i class="menu-icon icon-base ti tabler-tools"></i>
                                            <div>{{__('admin.features')}}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('view_car_option')
                                    <li class="menu-item {{Route::is(['admin.car_options.index'])? 'active' : ''}}">
                                        <a href="{{route('admin.car_options.index')}}" class="menu-link">
                                            <i class="menu-icon icon-base ti tabler-tools"></i>
                                            <div>{{__('admin.car_options')}}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('view_transportation')
                                    <li class="menu-item {{Route::is(['admin.transportations.index'])? 'active' : ''}}">
                                        <a href="{{route('admin.transportations.index')}}" class="menu-link">
                                            <i class="menu-icon icon-base ti tabler-brand-cashapp"></i>
                                            <div>{{__('admin.transportations')}}</div>
                                        </a>
                                    </li>
                                @endcan
                                @if(\Illuminate\Support\Facades\Gate::any(['view_time']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('scan_times'))
                                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('scan_times') ? 'active open' : '' }} {{Route::is(['admin.times.index','admin.area_times.index'])? 'active' : ''}}">
                                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                                            <i class="menu-icon icon-base ti tabler-clock-hour-2"></i>
                                            <div>{{__('admin.scan_times')}}</div>
                                        </a>
                                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'scan_times'])
                            @can('view_time')
                                            <li class="menu-item {{Route::is(['admin.times.index'])? 'active' : ''}}">
                                                <a href="{{route('admin.times.index')}}" class="menu-link">
                                                    <div>{{__('admin.scan_times')}}</div>
                                                </a>
                                            </li>
                                            <li class="menu-item {{Route::is(['admin.ignore_dates.index'])? 'active' : ''}}">
                                                <a href="{{route('admin.ignore_dates.index')}}" class="menu-link">
                                                    <div>{{__('admin.ignore_dates')}}</div>
                                                </a>
                                            </li>
                                            <li class="menu-item {{Route::is(['admin.area_times.index'])? 'active' : ''}}">
                                                <a href="{{route('admin.area_times.index')}}" class="menu-link">
                                                    <div>{{__('admin.area_times')}}</div>
                                                </a>
                                            </li>

                                        @endcan
                            </ul>
                                    </li>

                                @endif
                                @can('view_part')
                                    <li class="menu-item {{Route::is(['admin.parts.index'])? 'active' : ''}}">
                                        <a href="{{route('admin.parts.index')}}" class="menu-link">
                                            <i class="menu-icon icon-base ti tabler-tools"></i>
                                            <div>{{__('admin.parts')}}</div>
                                        </a>
                                    </li>
                                @endcan
                        </ul>
                    </li>
                @endif
                @if(\Illuminate\Support\Facades\Gate::any(['view_customer_service','view_customer_service_fees','view_customer_service_employee','view_customer_service_employee_fee','view_optional_phone']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('customer_service'))
                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('customer_service') ? 'active open' : '' }} {{Route::is(['admin.customer_services.index','admin.customer_service_fees.index',
                                        'admin.customer_service_employees.index','admin.customer_service_employee_fees.index'])? 'active' : ''}}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-user"></i>
                            <div>{{__('admin.customer_service')}}</div>
                        </a>
                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'customer_service'])
                            @can('view_customer_service')
                                <li class="menu-item {{Route::is(['admin.customer_services.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.customer_services.index')}}" class="menu-link">
                                        <div>{{__('admin.companies')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_customer_service_fee')
                                <li class="menu-item {{Route::is(['admin.customer_service_fees.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.customer_service_fees.index')}}" class="menu-link">
                                        <div>{{__('admin.customer_service_fees')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_customer_service_employee')
                                <li class="menu-item {{Route::is(['admin.customer_service_employees.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.customer_service_employees.index')}}" class="menu-link">
                                        <div>{{__('admin.customer_service_employees')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_customer_service_employee_fee')
                                <li class="menu-item {{Route::is(['admin.customer_service_employee_fees.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.customer_service_employee_fees.index')}}" class="menu-link">
                                        <div>{{__('admin.customer_service_employee_fees')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_optional_phone')
                                <li class="menu-item {{Route::is(['admin.optional_phones.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.optional_phones.index')}}" class="menu-link">
                                        <div>{{__('admin.optional_phones')}}</div>
                                    </a>
                                </li>
                            @endcan
                        </ul>
                    </li>
                @endif
                    @if(\Illuminate\Support\Facades\Gate::any(['view_city','view_area']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('zones'))
                        <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('zones') ? 'active open' : '' }} {{Route::is(['admin.cities.index','admin.areas.index'])? 'active' : ''}}">
                            <a href="javascript:void(0);" class="menu-link menu-toggle">
                                <i class="menu-icon icon-base ti tabler-building-community"></i>
                                <div>{{__('admin.zones')}}</div>
                            </a>
                            <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'zones'])
                                @can('view_city')
                                    <li class="menu-item {{Route::is(['admin.cities.index'])? 'active' : ''}}">
                                        <a href="{{route('admin.cities.index')}}" class="menu-link">
                                            <div>{{__('admin.cities')}}</div>
                                        </a>
                                    </li>
                                @endcan
                                @can('view_area')
                                    <li class="menu-item {{Route::is(['admin.areas.index'])? 'active' : ''}}">
                                        <a href="{{route('admin.areas.index')}}" class="menu-link">
                                            <div>{{__('admin.areas')}}</div>
                                        </a>
                                    </li>
                                @endcan


                            </ul>
                        </li>
                    @endif
                @if(\Illuminate\Support\Facades\Gate::any(['view_brand','view_brand_model']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('cars'))
                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('cars') ? 'active open' : '' }} {{Route::is(['admin.brands.index','admin.brand_models.index','admin.brand_model_classes.index'])? 'active' : ''}}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-car"></i>
                            <div>{{__('admin.cars')}}</div>
                        </a>
                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'cars'])
                            @can('view_brand')
                                <li class="menu-item {{Route::is(['admin.brands.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.brands.index')}}" class="menu-link">
                                        <div>{{__('admin.brands')}}</div>
                                    </a>
                                </li>
                            @endcan
                            @can('view_brand_model')
                                <li class="menu-item {{Route::is(['admin.brand_models.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.brand_models.index')}}" class="menu-link">
                                        <div>{{__('admin.brand_models')}}</div>
                                    </a>
                                </li>
                                    <li class="menu-item {{Route::is(['admin.brand_model_classes.index'])? 'active' : ''}}">
                                    <a href="{{route('admin.brand_model_classes.index')}}" class="menu-link">
                                        <div>{{__('admin.brand_model_classes')}}</div>
                                    </a>
                                </li>
                            @endcan



                        </ul>
                    </li>
                @endif
                @if(\Illuminate\Support\Facades\Gate::any(['view_faq']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('faqs'))
                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('faqs') ? 'active open' : '' }} {{Route::is(['admin.faq_categories.index','admin.faq.index'])? 'active' : ''}}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-help-octagon"></i>
                            <div>{{__('admin.faqs')}}</div>
                        </a>
                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'faqs'])
                            @can('view_faq')
                            <li class="menu-item {{Route::is(['admin.faq_categories.index'])? 'active' : ''}}">
                                <a href="{{route('admin.faq_categories.index')}}" class="menu-link">
                                    <div>{{__('admin.faq_categories')}}</div>
                                </a>
                            </li>
                            <li class="menu-item {{Route::is(['admin.faqs.index'])? 'active' : ''}}">
                                <a href="{{route('admin.faqs.index')}}" class="menu-link">
                                    <div>{{__('admin.faqs')}}</div>
                                </a>
                            </li>

                        @endcan
                            </ul>
                    </li>
                @endif
                @can('view_banner')
                    <li class="menu-item {{Route::is(['admin.banners.index'])? 'active' : ''}}">
                        <a href="{{route('admin.banners.index')}}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-carousel-horizontal"></i>
                            <div>{{__('admin.banners')}}</div>
                        </a>
                    </li>
                @endcan

                @can('view_highlight')
                    <li class="menu-item {{Route::is(['admin.highlights.index'])? 'active' : ''}}">
                        <a href="{{route('admin.highlights.index')}}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-highlight"></i>
                            <div>{{__('admin.highlights')}}</div>
                        </a>
                    </li>
                @endcan
                    @can('view_wanted')
                    <li class="menu-item {{Route::is(['admin.wanted.index'])? 'active' : ''}}">
                        <a href="{{route('admin.wanted.index')}}" class="menu-link">
                            <i class="menu-icon icon-base ti tabler-basket-question"></i>
                            <div>{{__('admin.wanted')}}</div>
                        </a>
                    </li>
                @endcan
{{--                @can('view_how_know_us')--}}
{{--                    <li class="menu-item {{Route::is(['admin.how_know_us.index'])? 'active' : ''}}">--}}
{{--                        <a href="{{route('admin.how_know_us.index')}}" class="menu-link">--}}
{{--                            <i class="menu-icon icon-base ti tabler-help-octagon"></i>--}}
{{--                            <div>{{__('admin.how_know_us')}}</div>--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @endcan--}}
                @if(\Illuminate\Support\Facades\Gate::any(['view_coupon']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('coupons'))
                    <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('coupons') ? 'active open' : '' }} {{Route::is(['admin.coupons.index','admin.seller_coupons.index']) ? 'active' : ''}}">
                        <a href="javascript:void(0);" class="menu-link menu-toggle">
                            <i class="menu-icon icon-base ti tabler-discount"></i>
                            <div>{{__('admin.coupons')}}</div>
                        </a>
                        <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'coupons'])
                            @can('view_coupon')

                                <li class="menu-item {{Route::is(['admin.coupons.index']) ? 'active' : ''}}">
                                    <a href="{{route('admin.coupons.index')}}" class="menu-link">
                                        <div>{{__('admin.coupons')}}</div>
                                    </a>
                                </li>
 <li class="menu-item {{Route::is(['admin.seller_coupons.index']) ? 'active' : ''}}">
                                    <a href="{{route('admin.seller_coupons.index')}}" class="menu-link">
                                        <div>{{__('admin.seller_coupons')}}</div>
                                    </a>
                                </li>


                        @endcan
                            </ul>
                    </li>
                @endif
{{--                @can('view_insurance')--}}
{{--                    <li class="menu-item {{Route::is(['admin.insurance.index'])? 'active' : ''}}">--}}
{{--                        <a href="{{route('admin.insurance.index')}}" class="menu-link">--}}
{{--                            <i class="menu-icon icon-base ti tabler-brand-cashapp"></i>--}}
{{--                            <div>{{__('admin.insurance')}}</div>--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @endcan--}}
{{--                @can('view_insurance_package')--}}
{{--                    <li class="menu-item {{Route::is(['admin.insurance_packages.index'])? 'active' : ''}}">--}}
{{--                        <a href="{{route('admin.insurance_packages.index')}}" class="menu-link">--}}
{{--                            <i class="menu-icon icon-base ti tabler-brand-cashapp"></i>--}}
{{--                            <div>{{__('admin.insurance_packages')}}</div>--}}
{{--                        </a>--}}
{{--                    </li>--}}
{{--                @endcan--}}



{{--                @can('view_price_step')--}}
{{--                <li class="menu-item {{Route::is(['admin.price_steps.index'])? 'active' : ''}}">--}}
{{--                    <a href="{{route('admin.price_steps.index')}}" class="menu-link">--}}
{{--                        <i class="menu-icon icon-base ti tabler-tools"></i>--}}
{{--                        <div>{{__('admin.price_steps')}}</div>--}}
{{--                    </a>--}}
{{--                </li>--}}
{{--                @endcan--}}

                    @can('view_service')
                        <li class="menu-item {{Route::is(['admin.services.index'])? 'active' : ''}}">
                            <a href="{{route('admin.services.index')}}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-category"></i>
                                <div>{{__('admin.services')}}</div>
                            </a>
                        </li>
                    @endcan
                    @can('view_service')
                        <li class="menu-item {{Route::is(['admin.service_requests.index'])? 'active' : ''}}">
                            <a href="{{route('admin.service_requests.index')}}" class="menu-link">
                                <i class="menu-icon icon-base ti tabler-category"></i>
                                <div>{{__('admin.service_requests')}}</div>
                            </a>
                        </li>
                    @endcan






            @can('view_auction')
                <li class="menu-item {{Route::is(['admin.auctions.index','admin.auctions.show'])? 'active' : ''}}">
                    <a href="{{route('admin.auctions.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-folder-open"></i>
                        <div>{{__('admin.auctions')}}</div>
                    </a>
                </li>
            @endcan
                @can('view_bill')
                <li class="menu-item {{Route::is(['admin.bills.index'])? 'active' : ''}}">
                    <a href="{{route('admin.bills.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-file-invoice"></i>
                        <div>{{__('admin.bills')}}</div>
                    </a>
                </li>
            @endcan
            @can('view_notification')
                <li class="menu-item {{Route::is(['admin.notifications.index'])? 'active' : ''}}">
                    <a href="{{route('admin.notifications.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-bell-plus"></i>
                        <div>{{__('admin.notifications')}}</div>
                    </a>
                </li>
            @endcan
            @if(\Illuminate\Support\Facades\Gate::any(['view_ticket','view_ticket_category']) || app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->hasVisibleGroup('tickets'))
                <li class="menu-item {{ app(\ZeroOneZ\Dashboard\Services\Dashboard\ResourceRegistry::class)->groupActive('tickets') ? 'active open' : '' }} {{Route::is(['admin.tickets.index','admin.ticket_categories.index'])? 'active' : ''}}">
                    <a href="javascript:void(0);" class="menu-link menu-toggle">
                        <i class="menu-icon icon-base ti tabler-ticket"></i>
                        <div>{{__('admin.tickets')}}</div>
                    </a>
                    <ul class="menu-sub">
                            @include('zerooonez-dashboard::admin-components.generated-links', ['generatedGroup' => 'tickets'])
                        @can('view_ticket_category')
                            <li class="menu-item {{Route::is(['admin.ticket_categories.index'])? 'active' : ''}}">
                                <a href="{{route('admin.ticket_categories.index')}}" class="menu-link">
                                    <div>{{__('admin.ticket_categories')}}</div>
                                </a>
                            </li>
                        @endcan
                        @can('view_ticket')
                            <li class="menu-item {{Route::is(['admin.tickets.index'])? 'active' : ''}}">
                                <a href="{{route('admin.tickets.index')}}" class="menu-link">
                                    <div>{{__('admin.tickets')}}</div>
                                </a>
                            </li>
                        @endcan


                    </ul>
                </li>
            @endif
            @can('view_settings')
                <li class="menu-item {{Route::is(['admin.settings.index'])? 'active' : ''}}">
                    <a href="{{route('admin.settings.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-settings"></i>
                        <div>{{__('admin.settings')}}</div>
                    </a>
                </li>
            @endcan
            @can('view_email_type')
                <li class="menu-item {{Route::is(['admin.email_types.index'])? 'active' : ''}}">
                    <a href="{{route('admin.email_types.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-mail"></i>
                        <div>{{__('admin.emails')}}</div>
                    </a>
                </li>
            @endcan
            @can('view_permission')
                <li class="menu-item {{Route::is(['admin.permissions.index'])? 'active' : ''}}">
                    <a href="{{route('admin.permissions.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-lock"></i>
                        <div>{{__('admin.permissions')}}</div>
                    </a>
                </li>
            @endcan
        @endif



        @if(company()->check())
            <li class="menu-item {{Route::is(['company.scanners.index'])? 'active' : ''}}">
            <a href="{{route('company.scanners.index')}}" class="menu-link ">
                <i class="menu-icon icon-base ti tabler-smart-home "></i>
                <div >{{__('admin.scanners')}}</div>
                {{--                <div class="badge text-bg-danger rounded-pill ms-auto">3</div>--}}
            </a>
            </li>
            <li class="menu-item {{Route::is(['company.auctions.index'])? 'active' : ''}}">
                <a href="{{route('company.auctions.index')}}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-folder-open"></i>
                    <div>{{__('admin.auctions')}}</div>
                </a>
            </li>

        @endif
        @if(customer_service()->check())
            <li class="menu-item {{Route::is(['customer_service.customer_service_employees.index'])? 'active' : ''}}">
                <a href="{{route('customer_service.customer_service_employees.index')}}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-user "></i>
                    <div>{{__('admin.customer_service_employees')}}</div>
                </a>
            </li>
                <li class="menu-item {{Route::is(['customer_service.customer_service_fees.index'])? 'active' : ''}}">
                    <a href="{{route('customer_service.customer_service_fees.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-wallet "></i>
                        <div>{{__('admin.customer_service_fees')}}</div>
                    </a>
                </li>





                <li class="menu-item {{Route::is(['customer_service.customer_service_employee_fees.index'])? 'active' : ''}}">
                    <a href="{{route('customer_service.customer_service_employee_fees.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-wallet "></i>
                        <div>{{__('admin.customer_service_employee_fees')}}</div>
                    </a>
                </li>


                <li class="menu-item {{Route::is(['customer_service.optional_phones.index'])? 'active' : ''}}">
                    <a href="{{route('customer_service.optional_phones.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-phone "></i>
                        <div>{{__('admin.optional_phones')}}</div>
                    </a>
                </li>
        @endif
        @if(customer_service_employee()->check())

                <li class="menu-item {{Route::is(['customer_service_employee.optional_phones.index'])? 'active' : ''}}">
                    <a href="{{route('customer_service_employee.optional_phones.index')}}" class="menu-link">
                        <i class="menu-icon icon-base ti tabler-phone "></i>
                        <div>{{__('admin.optional_phones')}}</div>
                    </a>
                </li>
        @endif

        @include('zerooonez-dashboard::admin-components.generated-navigation')
    </ul>
</aside>

<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="ti tabler-menu icon-base"></i>
        <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>
