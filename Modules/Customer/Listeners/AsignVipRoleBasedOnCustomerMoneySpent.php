<?php

namespace Modules\Customer\Listeners;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Modules\Core\Classes\CoreSettings;
use Modules\Customer\Entities\Customer;
use Modules\Customer\Entities\CustomerRole;
use Modules\Order\Entities\Order;
use Modules\Order\Events\OrderDelivered;
use Modules\Setting\Entities\Setting;

class AsignVipRoleBasedOnCustomerMoneySpent
{
    /**
     * Create the event listener.
     *
     * @return void
     */

    public $enabled ;
    public $vipSettings;

    public Order $order;
    public Customer $customer;


    public function __construct(CoreSettings $setting)
    {
        //
        $this->enabled = $setting->get('vip') && ($setting->get('vip')['enabled'] ?? null);
        $this->vipSettings = Setting::query()->whereGroup('vip')->get();

        
    }

    /**
     * Handle the event.
     *$this->order
     * @param  object  $event
     * @return void
     */
    public function handle(OrderDelivered $event)
    {
        Log::debug('listener called!');
        $vip_role = CustomerRole::query()->whereIn('name',['vip','VIP'])->first();
        if($this->preventHandling() || !$vip_role){
            return;
        }

        $this->customer = $event->order->customer;
        $this->order = $event->order;

        if($this->customer->total_money_spent >= ((int)$this->getVipRoleAsignmentThreshold())){
            $this->customer->role()->associate($vip_role)->save();
        }

    }

    public function preventHandling() : bool
    {
        return !$this->enabled;
    }

    public function getVipRoleAsignmentThreshold(){
        if(!$this->enabled){
            return;
        }
        return $this->vipSettings->where('name','threshold')->first()->value;
    }
}
