<?php

namespace Modules\Order\Http\Controllers\Admin;

use Modules\Core\Http\Controllers\BaseController;
use Modules\Order\Entities\Seller;
use Modules\Order\Http\Requests\Admin\SellerStoreRequest;

class SellerController extends BaseController
{
    public function index()
    {
        $sellers = Seller::latest()->paginateOrAll();

        return response()->success('', compact('sellers'));
    }

    public function store(SellerStoreRequest $request)
    {
        $seller = Seller::create($request->all());

        return response()->success('', compact('seller'));
    }

    public function update(SellerStoreRequest $request, Seller $seller)
    {
        $seller->update($request->all());

        return response()->success('', compact('seller'));
    }

    public function destroy(Seller $seller)
    {
        $seller->delete();

        return response()->success('', compact('seller'));
    }
}
