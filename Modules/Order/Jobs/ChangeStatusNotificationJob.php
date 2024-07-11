<?php

namespace Modules\Order\Jobs;

use Modules\Order\Entities\Order;
use Shetabit\Shopit\Modules\Sms\Sms;
use Modules\Core\Classes\CoreSettings;
use Shetabit\Shopit\Modules\Order\Jobs\ChangeStatusNotificationJob as BaseChangeStatusNotificationJob;

class ChangeStatusNotificationJob extends BaseChangeStatusNotificationJob
{
    public function sms($order, $customer)
    {
    $coreSettings = app(CoreSettings::class);
    $orderId =  $this->order->reserved_id ?: $this->order->id;
    $pattern = $coreSettings->get('sms.patterns.change_status');
    $address = json_decode($order->address);
    $full_name = $customer->full_name ?: $address->first_name.' '.$address->last_name;

    // ترتیب این کلید ها به هیچ وجه نباید عوض بشه
    $data = [
        // 'full_name' => str_replace(' ','_',$full_name),
        'status' => ($order->status == 'delivered') ? 'درحالـارسال' : str_replace(' ','_',__('core::statuses.'.$order->status)),
        'order_id' => $orderId,
    ];

    // اگر اس ام اس اختصاصی نفرستادی دیفالت و بفرست
    if (!$this->sendCustomSms($order, $customer, $data)
        && in_array($order->status, [Order::STATUS_CANCELED, Order::STATUS_DELIVERED])
    ){
        Sms::pattern($pattern)->data($data)->to([$customer->mobile])->send();
    }
}
}
