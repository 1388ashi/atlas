<?php

namespace Modules\Order\Http\Controllers\Admin;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Customer\Entities\Customer;
use Modules\Order\Entities\MiniOrder;
use Modules\Order\Entities\MiniOrderItem;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Variety;
use Shetabit\Shopit\Modules\Core\Classes\CoreSettings;
use Shetabit\Shopit\Modules\Order\Http\Requests\Admin\MiniOrderStoreRequest;
use Shetabit\Shopit\Modules\Sms\Sms;

class MiniOrderController extends \Shetabit\Shopit\Modules\Order\Http\Controllers\Admin\MiniOrderController
{

    public function search(Request $request)
    {
        $request->validate([
            'sku' => 'required'
        ]);

        $variety = Variety::query()->withCommonRelations()->where('barcode', $request->sku)->first();

        if ($variety->product->status == Product::STATUS_INIT_QUANTITY){
            return response()->error('امکان فروش محصول مورد نظر وجود ندارد');
        }
        if (!$variety) {
            return response()->error('محصول مورد نظر یافت نشد', null, 404);
        }
        if (!$variety->store->balance && !$request->refund) {
            return response()->error('محصول مورد نظر موجودی ندارد');
        }

        return response()->success('', compact('variety'));
    }

    public function store(MiniOrderStoreRequest $request)
    {
        try {
            DB::beginTransaction();
            if ($request->mobile) {
                $alreadyExists = false;
                /** @var Customer $customer */
                $customer = Customer::query()->where('mobile', $request->mobile)->first();
                if ($customer) {
                    $alreadyExists = true;
                } else {
                    $customer = Customer::query()->create([
                        'mobile' => $request->mobile
                    ]);
                }
                $request->merge([
                    'customer_id' => $customer->id
                ]);

                if ($alreadyExists) {
                    $balance = $customer->balance;
                    if ($balance < $request->from_wallet_amount) {
                        return response()->error('مبلغ وارد شده از کیف پول مشتری بیشتر است');
                    }
                } else {
                    if ($request->from_wallet_amount) {
                        return response()->error('مبلغ وارد شده از کیف پول مشتری بیشتر است');
                    }
                }
            }
            /** @var MiniOrder $miniOrder */
            $miniOrder = new MiniOrder($request->all());
            $hasSell = $request->has('varieties') && count($request->varieties);
            $hasRefund = $request->has('refund_varieties') && count($request->refund_varieties);
            $type = match (true) {
                $hasRefund && $hasSell => MiniOrder::TYPE_BOTH,
                $hasRefund => MiniOrder::TYPE_REFUND,
                $hasSell => MiniOrder::TYPE_SELL,
                default => throw new \Exception('23214140'),
            };
            $miniOrder->type = $type;
            $miniOrder->save();

            foreach ($request->varieties as $varietyFromRequest) {
                /** @var Variety $variety */
                $variety = Variety::withCommonRelations()->findOrFail($varietyFromRequest['id']);
                MiniOrderItem::store($variety, $varietyFromRequest['quantity'],
                    $miniOrder, MiniOrderItem::TYPE_SELL, $varietyFromRequest['amount']);
            }

            foreach ($request->refund_varieties as $refundVarietyFromRequest) {
                /** @var Variety $variety */
                $variety = Variety::withCommonRelations()->findOrFail($refundVarietyFromRequest['id']);
                MiniOrderItem::store($variety, $refundVarietyFromRequest['quantity'],
                    $miniOrder, MiniOrderItem::TYPE_REFUND, $refundVarietyFromRequest['amount']);
            }

            if ($request->from_wallet_amount) {
                $transaction = $customer->withdraw($request->from_wallet_amount, [
                    'description' => 'خرید سفارش حضوری به شماره #' . $miniOrder->id
                ]);
                $miniOrder->transaction()->associate($transaction);
            }

            DB::commit();
        } catch (\Throwable $throwable) {
            DB::rollBack();

            Log::error($throwable->getTraceAsString());
            return response()->error('عملیات به مشکل خورد: ' . $throwable->getMessage(),
                $throwable->getTrace());
        }

        $miniOrder->load('customer');

        if($request->mobile){
            //send sms
            if (!app(CoreSettings::class)->get('sms.patterns.success_mini_order', false)) {
                return response()->success('سفارش با موفقیت ایجاد شد', ['mini_order' => $miniOrder]);
            }

            $pattern = app(CoreSettings::class)->get('sms.patterns.success_mini_order');

            Sms::pattern($pattern)->data([
                'token' => 'محصولات'
            ])->to([$request->mobile])->send();
        }

        return response()->success('سفارش با موفقیت ایجاد شد', ['mini_order' => $miniOrder]);
    }

}
