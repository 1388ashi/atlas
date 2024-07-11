<?php

namespace Modules\Color\Http\Controllers\Front;
use Modules\Color\Entities\Color;
use Modules\Core\Helpers\Helpers;
use Modules\Core\Http\Controllers\BaseController;

class ColorController extends BaseController
{
    public function getColors()
    {
        return Helpers::cacheRemember('home_colors', 3600, function () {
            return Color::query()->select(['id', 'name', 'code'])->active()->get()->toArray();
        });
    }
}
