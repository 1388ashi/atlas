<?php

namespace Modules\Cart\Http\Controllers\Front;
use Illuminate\Http\Request;
use Modules\Cart\Classes\CartFromRequest;
use Modules\Core\Http\Controllers\BaseController;

class CartController extends BaseController
{
    // for cookie
    public function getCartFromRequest(Request $request)
    {
        return CartFromRequest::checkCart($request);
    }

}
