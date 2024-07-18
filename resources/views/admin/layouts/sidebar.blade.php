<div class="app-sidebar__user">
    <div class="dropdown user-pro-body text-center align-items-center   ">
            <span style="font-size: 20px" class="text-light">اطلس مد</span>
    </div>
</div>
<div class="app-sidebar3 mt-0">
    <ul class="side-menu">
        <li class="slide">
            <a class="side-menu__item" data-toggle="slide" href="{{route('admin.dashboard')}}">
                <i class="fe fe-home fa fa-dashboard sidemenu_icon"></i>
                <span class="side-menu__label">داشبورد</span></i>
            </a>
        </li>
        @role('super_admin')
        {{-- @if(Auth::user()->role->name == 'super_admin') --}}
        <li class="slide">
            <a class="side-menu__item" style="cursor: pointer" data-toggle="slide" >
                <i class="feather feather-edit sidemenu_icon"></i>
                <span  class="side-menu__label">اطلاعات پایه</span><i class="angle fa fa-angle-left"></i>
            </a>
            <ul class="slide-menu">
                <li><a href="{{route('admin.roles.index')}}" class="slide-item">مدیریت نقش ها</a></li>
            </ul>
            <ul class="slide-menu">
                <li><a href="{{route('admin.admins.index')}}" class="slide-item">مدیریت ادمین ها</a></li>
            </ul>
        </li>
        @endrole
    </ul>
</div>
