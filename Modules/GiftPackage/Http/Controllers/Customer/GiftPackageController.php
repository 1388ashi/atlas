<?php

namespace Modules\GiftPackage\Http\Controllers\Customer;

use DB;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Area\Entities\City;
use Modules\GiftPackage\Entities\GiftPackage;
use Modules\GiftPackage\Http\Requests\Admin\GiftPackageCityAssignRequest;
use Modules\GiftPackage\Http\Requests\Admin\GiftPackageStoreRequest;
use Modules\GiftPackage\Http\Requests\Admin\GiftPackageUpdateRequest;

class GiftPackageController extends Controller
{
    public function index()
    {
        $gift_packages = GiftPackage::query()
            ->active()->latest('order')->get();

        return response()->success('', compact('gift_packages'));
    }
}
