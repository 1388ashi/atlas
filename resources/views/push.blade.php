<?php

//$product = \Modules\Product\Entities\Product::find(8);
////var_dump(\Modules\Product\Entities\ListenCharge::send($product));
//var_dump(\Modules\Product\Jobs\SendFirebaseNotificationJob::dispatch($product));
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Kutia\Larafirebase\Messages\FirebaseMessage;
use Modules\Customer\Entities\SmsToken;
use Modules\Order\Jobs\ChangeStatusNotificationJob;
use Shetabit\Shopit\Modules\Customer\Notifications\DepositWalletFailedNotification;

//$tokens = DB::table('personal_access_tokens')
//        ->where('tokenable_type', 'Modules\Customer\Entities\Customer')
//        ->whereNotNull('device_token')
//        ->where('tokenable_id', 17)
//        ->get('device_token')->pluck('device_token')->toArray();
//
//    $message =  (new FirebaseMessage())
//        ->withTitle('زمان رزرو شما رو به پایان است')
//        ->withBody("سلام اطلسی عزیز مدت زمان سفارش رزرو شده شما به شناسه درحال اتمام است ");
//$message->asNotification(array_values(array_unique($tokens)));

//$customer = \Modules\Customer\Entities\Customer::find(17);
//$customer->notify(new \Shetabit\Shopit\Modules\Customer\Notifications\DepositWalletSuccessfulNotification($customer, 50000));

//\Modules\Order\Jobs\ChangeStatusToFailedJob::dispatch();

//$order = \Modules\Order\Entities\Order::find(192);
//ChangeStatusNotificationJob::dispatch($order);
///** @var \Modules\Order\Entities\Order $order */
//$order = \Modules\Order\Entities\Order::find(107);
//dump($order->calculateShippingAmount());


?>
