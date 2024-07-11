<?php

namespace Modules\Customer\Http\Controllers\Front;

use Illuminate\Http\Request;
use Modules\Cart\Classes\CartFromRequest;
use Modules\Core\Http\Controllers\BaseController;
use Modules\Core\Services\NotificationService;

class CustomerController extends BaseController
{
    public function getUser(Request $request)
    {
        $user = \Auth::guard('customer-api')->user();
        $notificationService = $user == null ? null : new NotificationService($user);
        $user?->load('listenCharges');
        return [
            'user' => ($user == null) ? false : $user->loadCommonRelations(),
            'device_token' => ($user == null) ? false : $user->currentAccessToken()->device_token,
            'login' => !(($user == null)),
            'cart' => ($user ==  null) ? null : $user->carts()->withCommonRelations()->get(),
            'notifications' => ($user == null) ? null : [
                'items' => $notificationService->get(),
                'total_unread' => $notificationService->getTotalUnread()
            ],
            'cartRequest' =>  CartFromRequest::checkCart($request),
        ];
    }
}
