<?php

namespace Modules\Dashboard\Http\Controllers\Admin;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Shetabit\Shopit\Modules\Dashboard\Http\Controllers\Admin\DashboardController as BaseDashboardController;

class DashboardController extends BaseDashboardController
{
    public function index(){
        return view('admin.pages.dashboard');
    }
}
