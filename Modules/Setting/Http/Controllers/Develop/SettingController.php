<?php

namespace Modules\Setting\Http\Controllers\Develop;

use Shetabit\Shopit\Modules\Setting\Http\Controllers\Develop\SettingController as BaseSettingController;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Modules\Setting\Entities\Setting;
use Modules\Setting\Http\Requests\Develop\SettingStoreRequest;
use Modules\Setting\Http\Requests\Develop\SettingUpdateRequest;
use Modules\Setting\Http\Traits\PushToConfig;

class SettingController extends BaseSettingController
{
   public function index()
    {
     die();
        $find = request('find', false);

        $settings = Setting::query()->when($find, function ($query) use($find){
           $query->where('name', 'like', '%'.$find.'%')->latest();
        }, function ($query){
            $query->latest();
        })->get();

        return view('setting::index' , compact('settings'));
    }

}
