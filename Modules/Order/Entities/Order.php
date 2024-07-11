<?php

namespace Modules\Order\Entities;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\Cart\Entities\Cart;
use Modules\Admin\Entities\Admin;
use Illuminate\Support\Facades\DB;
use Modules\Core\Classes\CoreSettings;
use Modules\Coupon\Entities\Coupon;
use Modules\Customer\Notifications\InvoicePaid;
use Modules\Invoice\Entities\Invoice;
use Modules\Invoice\Entities\Payment;
use Modules\Order\Jobs\NewOrderForCustomerNotificationJob;
use Modules\Order\Mail\NewOrderEmail;
use Modules\Product\Entities\Variety;
use Modules\Customer\Entities\Customer;
use Modules\GiftPackage\Entities\GiftPackage;
use Modules\Invoice\Events\GoingToVerifyPayment;
use Modules\Invoice\Listeners\CheckStoreOnVerified;
use Modules\Setting\Entities\Setting;
use Modules\Store\Entities\Store;
use Shetabit\Shopit\Modules\Order\Entities\Order as BaseOrder;
use Shetabit\Shopit\Modules\Sms\Sms;

class Order extends BaseOrder
{
    public static $commonRelations = [
        'customer', 'statusLogs', 'items', 'invoices.payments', 'shipping', 'orderLogs','gift_package'
    ];
    protected $fillable = [
        'shipping_id',
        'coupon_id',
        'address',
        'shipping_amount',
        'discount_amount',
        'description',
        'status',
        'status_detail',
        'delivered_at',
        'reserved',
        'shipping_packet_amount',
        'shipping_more_packet_price',
        'shipping_first_packet_size',
        'gift_package_id',
        'gift_package_price'
    ];

    public function gift_package(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(GiftPackage::class,'gift_package_id');
    }

        // محاسبه قیمت نهایی
        public function getTotalAmount(): int
        {
            $activeItems = $this->items->where('status', 1);
            $totalItemsAmount = $activeItems
                ->reduce(function ($total, $item) {
                    return $total + ($item->amount * $item->quantity);
                });
            $giftPackageAmount = isset($this->attributes['gift_package_price']) ? $this->attributes['gift_package_price'] : 0;
//            dd($giftPackageAmount);

            return ($totalItemsAmount + $this->attributes['shipping_amount']) + $giftPackageAmount - $this->attributes['discount_amount'];
//            return ($totalItemsAmount + $this->attributes['shipping_amount']) + $giftPackageAmount; // به دلیل محاسبه شدن تخفیف در $totalItemsAmount متغیر آن در انتها نباید مجدداً کسر شود
        }

        public function getTotalAmountForAdmin()
        {
            $activeItems = $this->items->where('status', 1);
            $totalItemsAmount = $activeItems
                ->reduce(function ($total, $item) {
                    return $total + ($item->amount * $item->quantity);
                });
            $giftPackageAmount = isset($this->attributes['gift_package_price']) ? $this->attributes['gift_package_price'] : 0;
//            dd($giftPackageAmount);

            return ($totalItemsAmount + $this->attributes['shipping_amount']) + $giftPackageAmount ;
//            return ($totalItemsAmount + $this->attributes['shipping_amount']) + $giftPackageAmount; // به دلیل محاسبه شدن تخفیف در $totalItemsAmount متغیر آن در انتها نباید مجدداً کسر شود

        }

        public static function store(Customer $customer, $request)
        {
            /** @var Customer $user */
            $user = auth()->user();
            try {
                \DB::beginTransaction();
                $ORDER = new static;
                /**
                 * @var Cart $fakeCart
                 * @var $properties OrderStoreProperties
                 */
                $properties = $request->orderStoreProperties;
                $order = new static();

                $order->fill([
                    'shipping_id' => $properties->shipping->id,
                    'shipping_more_packet_price' => $properties->shipping->more_packet_price,
                    'shipping_first_packet_size' => $properties->shipping->first_packet_size,
                    'shipping_packet_amount' => $properties->shipping_packet_amount,
                    'address' => $properties->address->toJson(),
                    'coupon_id' => $properties->coupon ? $properties->coupon->id : null,
                    'shipping_amount' => $properties->shipping_amount,
                    'discount_amount' => $properties->discount_amount,
                    'delivered_at' => $request->delivered_at,
                    'status' => static::STATUS_WAIT_FOR_PAYMENT,
                    'reserved' => $request->reserved ?? 0,
                    'description' => $request->description,
                    'gift_package_id' => $request?->gift_package_id,
                    'gift_package_price' => $request->reserved ? 0: $request->gift_package_price
                ]);

                $order->customer()->associate($customer);
                $order->address()->associate($properties->address);
                if ($request->reserved) {
                    $order = $ORDER->associateReserved($order, $customer, $properties);
                }

                $order->save();


                //Create status log
                $order->statusLogs()->create([
                    'status' => static::STATUS_WAIT_FOR_PAYMENT
                ]);

                //store items
                /**
                 * زمانی که خود مشتری میخره باید از کارت بره توی اوردر ولی
                 * زمانی که ادمین میخره کارت باید نادیده گرفته بشه
                 */
                if (auth()->user() instanceof Admin) {
                    $fakeCart = new Cart();
                    foreach ($request->varieties as $variety) {
                        $baseVariety = Variety::query()->with(['product', 'product.activeFlash'])->findOrFail($variety['id']);
                        $fakeCart->fill([
                            'quantity' => $variety['quantity'],
                        ]);
                        $fakeCart->setPrice($baseVariety);
                        $fakeCart->variety()->associate($baseVariety);
                        $ORDER->addItemsInOrder($order, $fakeCart);
                    }
                } else {
                    foreach ($properties->carts as $cart) {
                        $ORDER->addItemsInOrder($order, $cart);
                    } //End of foreach
                }
                /**
                 * کم کردن از انبار توی ایونت موقع پرداخت صورت میگیره
                 * @see  CheckStoreOnVerified::store Listener
                 * @see  GoingToVerifyPayment::__construct Event
                 */
                DB::commit();
            } catch (\Exception $exception) {
                DB::rollBack();
                throw $exception;
            }
            $order->load('items');

            return $order;
        }

    public function onSuccessPayment(Invoice $invoice)
    {
        $this->status = $this->reserved ? static::STATUS_RESERVED : static::STATUS_NEW;
        $this->save();
        $wallet = $invoice->type == 'wallet' ? 1 : 0;
        $type = $wallet ? 'از کیف پول' : 'از درگاه پرداخت';

        /** @var OrderItem $item */
        foreach ($this->items as $item) {
            if ($item->flash_id) {
                DB::table('flash_product')
                    ->where('product_id', $item->product_id)
                    ->where('flash_id', $item->flash_id)
                    ->update([
                        'sales_count' => DB::raw("sales_count + {$item->quantity}")
                    ]);
            }
            Store::insertModel((object)[
                'variety_id' => $item->variety->id,
                'description' => "محصول {$item->variety->title} {$type} توسط مشتری با شناسه {$this->customer_id} در سفارش {$this->id} خریداری شد ",
                'type' => Store::TYPE_DECREMENT,
                'quantity' => $item->quantity,
                'order_id' => $this->id
            ]);
        }

        //Send Sms For Admin
        $pattern = app(CoreSettings::class)->get('sms.patterns.success-order');

        $adminMobile= Setting::query()->where('name','mobile_per_success_order')
            ->first();

        Sms::pattern($pattern)->data([
            'price' => number_format($invoice->amount *10),
            'date' => verta(now())->formatDate(),
            'time' => verta(now())->formatTime(),
        ])->to([$adminMobile->value])->send();


        // ذخیره سازی کوپن
        if ($this->coupon_id) {
            Coupon::useCoupon($this->customer_id, $this->coupon_id);
        }

        if ($this->reserved_id) {
            static::query()->findOrFail($this->reserved_id)
                ->increment('shipping_amount', $this->shipping_amount);
            $this->shipping_amount = 0;
            $this->save();
        }
        /** Clear customer basket for new purchases  */
        $this->customer->carts()->delete();


        // notify with sms and notification for customer & notify new order to admin with email
        try {
            NewOrderForCustomerNotificationJob::dispatch($this);
            $this->customer->notify(new InvoicePaid($this));
            $adminEmail = Setting::getFromName('new_order_email_address');
            Mail::to($adminEmail)->send(new NewOrderEmail($invoice->payable));
        } catch (\Exception $e) {
            Log::error('error on notify new order to admin ',[$e->getMessage()]);
            Log::error('error on notify new order to admin ',[$e->getTraceAsString()]);
        }
        if ($wallet) {
            $data = [
                'order_id' => $invoice->payable_id,
                'invoice_id' => $invoice->id,
                'need_pay' => 0
            ];

            return response()->success('خرید با موفقیت انجام شد.', $data);
        }

        return $this->callBackViewPayment($invoice);
    }

    public function scopeApplyFilter($query, $requestParams)
    {
        extract($requestParams);

        $query->when($product_id || $variety_id, function ($query) use ($product_id, $variety_id) {
            $query->whereHas('items', function ($query) use ($product_id, $variety_id) {
                $query->when($product_id && !$variety_id, function ($query) use ($product_id) {
                    $query->where('product_id', $product_id);
                })
                    ->when($variety_id, function ($query) use ($variety_id) {
                        $query->where('variety_id', $variety_id);
                    });
            });
        });

        $query->when($city, function ($query) use ($city) {
            $query->where('address->city->name', 'LIKE', '%'.$city.'%');
        })
            ->when($first_name, function ($query) use ($first_name) {
                $query->where('address->first_name', 'LIKE', '%'.$first_name.'%');
            })
            ->when($last_name, function ($query) use ($last_name) {
                $query->where('address->last_name', 'LIKE', '%'.$last_name.'%');
            })
            ->when($province, function ($query) use ($province) {
                $query->where('address->city->province->name', 'LIKE', '%'.$province.'%');
            })
            ->when($tracking_code, function ($query) use ($tracking_code) {
                $invoiceIds = Payment::query()
                    ->where('tracking_code', 'LIKE', "%$tracking_code%")
                    ->pluck('invoice_id');

                $orderIds = Invoice::query()
                    ->whereIn('id', $invoiceIds)
                    ->where('payable_type', Order::class)
                    ->pluck('payable_id');
                $query->whereIn('id', $orderIds);
            });

        return $query;
    }
}
