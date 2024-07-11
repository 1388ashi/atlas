<?php

namespace Modules\Attribute\Http\Controllers\Front;

use Modules\Attribute\Entities\Attribute;
use Modules\Core\Helpers\Helpers;
use Modules\Core\Http\Controllers\BaseController;

class AttributeController extends BaseController
{
    public function getSizeValues()
    {
        return Helpers::cacheRemember('size_values', 3600, function () {
            $sizeAttribute = Attribute::whereName('size')->select('id')->first();
            if (!$sizeAttribute) {
                return [];
            }

            return [
                'id' => $sizeAttribute->id,
                'values' => $sizeAttribute->values()->select(['id', 'value'])->get()->toArray()
            ];
        });
    }
}
