<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme"
     id="layout-navbar">


    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0   d-xl-none ">
        <a class="nav-item nav-link px-0 me-xl-6" href="javascript:void(0)">
            <i class="icon-base ti tabler-menu-2 icon-md"></i>
        </a>
    </div>

    {{env("APP_NAME")}}
    <div class="navbar-nav-right d-flex align-items-center justify-content-end" id="navbar-collapse">


        <ul class="navbar-nav flex-row align-items-center ms-md-auto">


            @include('dashboard.layout.parts.header.admin-system-language')


            @include('dashboard.layout.parts.header.admin-system-theme')


            @include('dashboard.layout.parts.header.admin-system-shortcut')

            @include('dashboard.layout.parts.header.admin-system-notifications')

            @include('dashboard.layout.parts.header.admin-system-profile')

        </ul>
    </div>
</nav>
