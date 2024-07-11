<?php

namespace Modules\Setting\Http\Controllers\Front;


use Modules\Core\Helpers\Helpers;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Setting\Entities\Setting;

class SettingController extends BaseController
{
    public function getSetting()
    {
        return Helpers::cacheForever('settings', function (){
            return Setting::query()->where('private', false)->get()->toArray();
        });
    }
}
