<?php

namespace Modules\Order\Services\Statuses;

use Modules\Invoice\Entities\Invoice;
use Modules\Order\Entities\Order;
use Modules\Order\Events\OrderDelivered;
use Shetabit\Shopit\Modules\Order\Services\Statuses\ChangeStatus as BaseChangeStatus;

class ChangeStatus extends BaseChangeStatus
{

    public function GoToNewOrDeliveredORInProgress($beforeStatus, $newStatus): Order
    {
        if($newStatus == Order::STATUS_DELIVERED){
           event(new OrderDelivered($this->order));
        }
        
        if (!in_array($beforeStatus, self::SUCCESS_STATUS)) {
            $this->order->payWithWallet($this->customer);
            Invoice::withoutEvents(function (){
                $this->order->invoices()->latest('id')->first()->delete();
            });
            $this->order->orderLogs()->create([
                'amount' => $this->order->getTotalAmount(),
                'status' => $this->request->status
            ]);
        }
        // انبار در قسمت on success چک میشه

        return $this->setStatus($newStatus);
    }
}
