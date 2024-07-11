<?php

namespace Modules\Product\Http\Controllers\Admin;

use Shetabit\Shopit\Modules\Product\Http\Controllers\Admin\ProductSetController as BaseProductSetController;
use Illuminate\Http\Request;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Product\Entities\ProductSet;

class ProductSetController extends BaseProductSetController
{
    public function index()
    {
        $productSets = ProductSet::latest('id')->filters()->withCount('products')->paginate();

        return response()->success('', ['product_sets' => $productSets]);
    }
}
