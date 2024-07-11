<?php

namespace Modules\Order\Http\Controllers\Admin;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Modules\Core\Classes\CoreSettings;
use Modules\Core\Helpers\Helpers;
use Modules\Customer\Entities\Customer;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Order\Entities\Order;
use Modules\Order\Http\Requests\Admin\AddItemsRequest;
use Modules\Product\Entities\Product;
use Modules\Report\Entities\OrderReport;
use Modules\Store\Entities\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Order\Entities\OrderLog;
use Modules\Order\Entities\OrderItem;
use Modules\Order\Entities\OrderItemLog;
use Modules\Order\Jobs\ChangeStatusNotificationJob;
use Modules\Order\Http\Requests\Admin\OrderUpdateRequest;
use Modules\Order\Http\Requests\Admin\UpdateItemsRequest;
use Shetabit\Shopit\Modules\Order\Events\OrderChangedEvent;
use Modules\Order\Http\Requests\Admin\UpdateItemStatusRequest;
use Modules\Order\Services\Statuses\ChangeStatus;
use Shetabit\Shopit\Modules\Order\Http\Controllers\Admin\OrderController as BaseOrderController;
use Throwable;


class OrderController extends BaseOrderController
{
    public function index(): JsonResponse
    {
        $requestParams = [
            'product_id' => \request('product_id', false),
            'variety_id' => \request('variety_id', false),
            'tracking_code' => \request('tracking_code', false),
            'city' => \request('city', false),
            'province' => \request('province', false),
            'first_name' => \request('first_name', false),
            'last_name' => \request('last_name', false)
        ];

        $ordersQuery = Order::query()
            ->with('customer')
            ->withCount('items')
            ->with([
                'reservations' => function ($query) {
                    $query->where('status', Order::STATUS_RESERVED)
                        ->with('invoices.payments.invoice');
                }
            ])
            ->applyFilter($requestParams)
            ->parents()
            ->filters()
            ->latest('id');

        $orders = $ordersQuery
            ->paginateOrAll(app(CoreSettings::class)->get('order.admin.pagination', 10));

        foreach ($orders as $order) {
            $order->append('active_payment');
            $order->append('active_payments');
            $order->makeHidden('invoices');
            $order->total_amount_in_panel=$order->getTotalAmountForAdmin();
        }

        $copyOrderQuery = clone $ordersQuery;
        Helpers::removeWhere($copyOrderQuery->getQuery(), 'status');
        $order_statuses = Order::getAllStatuses($copyOrderQuery);

        return response()->success('Get optimized orders list :)', compact('orders','order_statuses'));
    }

    public function updateItemStatus(UpdateItemStatusRequest $request, OrderItem $orderItem): JsonResponse
    {
        event(new OrderChangedEvent($orderItem->order, $request));
        $order = $orderItem->order;
        $oldTotalAmount = $order->getTotalAmount();
        $parentOrder = $order->reserved_id == null ? $order : Order::findOrFail($order->reserved_id);

        try {
            $variety = $request->variety;
            $oldAddress = $order->address;
            $oldShipping = $order->shipping_id;
            $oldDiscountAmount = $order->discount_amount;

            DB::beginTransaction();
            if ($orderItem->status == $request->status) {
                return response()->success('وضعیت با موفقیت تغییر کرد', null);
            }

            $orderItem->update(['status' => $request->status]);
            $oldShippingAmount = $parentOrder->shipping_amount;
            $newShippingAmount = $parentOrder->calculateShippingAmount();
            $diffShippingAmount = ($request->status == 1) ? ($newShippingAmount - $oldShippingAmount) : ($oldShippingAmount - $newShippingAmount);
            $calculateAmount =
                    $orderItem->amount * $orderItem->quantity + $diffShippingAmount ;

            if ($request->status == 1) {
                $quantity = $orderItem->quantity;
                $amount = $calculateAmount;
                $wallet = ['type' => 'decrement', 'amount' => $amount];
                $store = ['type' => 'decrement', 'quantity' => $quantity];
            } elseif ($request->status == 0) {
                $quantity = $orderItem->quantity;
                $amount = $calculateAmount;
                $wallet = ['type' => 'increment', 'amount' => $amount];
                $store = ['type' => 'increment', 'quantity' => $quantity];
            }
            /** @var Customer $customer */
            $customer = $orderItem->order->customer()->first();
            if ($wallet['type'] == 'decrement') {
                $customer->withdraw($wallet['amount'], [
                    'name' => $customer->getFullNameAttribute(),
                    'mobile' => $customer->mobile,
                    'description' => "با تغییر وضعیت آیتم سفارش  به تعداد {$quantity} عدد به محصول {$variety->title} اضافه شد"
                ]);

                Store::insertModel((object)[
                    'type' => $store['type'],
                    'description' => "با تغییر وضعیت آیتم سفارش  به تعداد {$quantity} عدد به محصول {$variety->title} اضافه شد",
                    'quantity' => $store['quantity'],
                    'variety_id' => $variety->id
                ]);
            }

            if ($wallet['type'] == 'increment') {
                $customer->deposit($wallet['amount'], [
                    'name' => $customer->getFullNameAttribute(),
                    'mobile' => $customer->mobile,
                    'description' => "با تغییر وضعیت آیتم سفارش به تعداد {$quantity} عدد از محصول {$variety->title} کم شد"
                ]);

                Store::insertModel((object)[
                    'type' => $store['type'],
                    'description' => "با تغییر وضعیت آیتم سفارش  به تعداد {$quantity} عدد از محصول {$variety->title} کم شد",
                    'quantity' => $store['quantity'],
                    'variety_id' => $variety->id
                ]);
            }
            $parentOrder->recalculateShippingAmount();
            $order->load('items');
            $orderLog = OrderLog::addLog($order,
                ($order->getTotalAmount() - $oldTotalAmount),
                $order->discount_amount - $oldDiscountAmount,
                $order->address != $oldAddress ? $order->address : null,
                $order->shipping_id != $oldShipping ? $order->shipping_id : null
            );

            if ($request->status == 0) {
                $status = OrderItemLog::TYPE_DELETE;
            } else {
                $status = OrderItemLog::TYPE_NEW;
            }

            $orderItemLog = OrderItemLog::addLog($orderLog, $orderItem, $status, $orderItem->quantity);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            return response()->error('عملیات ناموفق ' . $e->getMessage(), $e->getTrace());
        }
        return response()->success('وضعیت با موفقیت تغییر کرد', compact('orderItemLog'));
    }

    public function pending_list(Request $request): JsonResponse
    {
        if($request->has('is_done')){
            $request->merge([
                'is_done' => ($request->is_done === 'true') ? 1 : 0
            ]);
        }

           $orderItems = OrderItem::query()->with(['order'])->latest()->when(!($request->is_done == true),function($q){
            $q->pendingOrderItems();
           },function($q){$q->doneOrderItems();})
           ->with(['variety'=>function($q){$q->withCommonRelations();}])
           ->withCommonRelations();


           $orderItems = $orderItems->paginateOrAll(50);
        // $storeTransactions = $storeTransactions->latest()->filters()->groupBy('store_id')->select('*')->addSelect(DB::raw('sum(quantity) as variety_count'))->paginateOrAll(50);


        return response()->success('لیست تراکنش های انبار', compact('orderItems'));
    }

    public function markAsDone(Request $request)
    {
        $orderItem = OrderItem::findOrFail($request->orderItem);
        $orderItem->update([
            'is_done' => true
        ]);
        // $orderItem->refresh();

        return response()->success('status changed successfully!', [
            'data' => $orderItem
        ]);
    }

    public function markAsDoneBatch(Request $request)
    {
        OrderItem::whereIn('id', $request->input('orderItems'))
            ->update([
                'is_done' => true
            ]);

        return response()->success('statuses changed successfully!', [
            'data' => OrderItem::whereIn('id', $request->input('orderItems'))->get()
        ]);
    }
}
