<?php

namespace Modules\Order\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Modules\Order\Entities\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Customer\Entities\Customer;
use Modules\Order\Http\Requests\User\OrderStoreRequest;
use Modules\Order\Jobs\NewOrderForAdminNotificationJob;

use Shetabit\Shopit\Modules\Order\Http\Controllers\Customer\OrderController as BaseOrderController;

class OrderController extends BaseOrderController
{
    public function index(): JsonResponse
    {
        /**
         * @var $user Customer
         */
        $status = request('status', false);

        $orders = Order::query()->when($status && $status != 'all', function ($query) use ($status){
              $query->where('status' , $status);
        })->myOrders()
            ->with(['statusLogs', 'invoices.payments','gift_package'])
            ->parents()
            ->filters()
            ->latest('id')
            ->paginateOrAll();
        $statistics = Order::getOrderStatisticsForCustomer(Auth::user());

        return response()->success('Get all orders list', compact('orders', 'statistics'));
    }
    public function store(OrderStoreRequest $request)
    {
        try {
            /** @var $customer Customer */
            $customer = auth()->user();
            /** @var $order Order */
            $order = Order::store($customer, $request);
            NewOrderForAdminNotificationJob::dispatch($order);
            //نوتیفیکیشن برای مشتری در قسمت on success
            // اگر کاربر بخواهد با کیف پرداخت کند
            if ($request->input('pay_wallet')) {
                return $order->payWithWallet($customer);
            } else {
                return $order->pay();
            }

        } catch (Exception $exception) {
            Log::error($exception->getTraceAsString());
            return response()->error('مشکلی در برنامه رخ داده است:' . $exception->getMessage(), $exception->getTrace(), 500);
        }
    }
}
