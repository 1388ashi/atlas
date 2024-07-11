<?php

//$product = \Modules\Product\Entities\Product::find(8);
////var_dump(\Modules\Product\Entities\ListenCharge::send($product));
//var_dump(\Modules\Product\Jobs\SendFirebaseNotificationJob::dispatch($product));
use Kutia\Larafirebase\Messages\FirebaseMessage;

$tokens = DB::table('personal_access_tokens')
        ->where('tokenable_type', 'Modules\Customer\Entities\Customer')
        ->whereNotNull('device_token')
        ->where('tokenable_id', 10)
        ->get('device_token')->pluck('device_token')->toArray();

    $message =  (new FirebaseMessage())
        ->withTitle('زمان رزرو شما رو به پایان است')
        ->withBody("سلام اطلسی عزیز مدت زمان سفارش رزرو شده شما به شناسه درحال اتمام است ");
    $message->asNotification(array_values(array_unique($tokens)));
    dd($message);
?>
