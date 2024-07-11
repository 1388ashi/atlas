<?php

namespace Modules\Customer\Entities;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Classes\CoreSettings;
use Modules\Core\Helpers\Helpers;
use Modules\Order\Entities\MiniOrder;
use Modules\Order\Entities\Order;
use Modules\Product\Entities\Product;
use Modules\Setting\Entities\Setting;
use Shetabit\Shopit\Modules\Customer\Entities\Customer as BaseCustomer;

class Customer extends BaseCustomer
{

    protected $appends = [
        'full_name',
        'image',
        'last_online_order_date',
        'last_real_order_date',
        'persian_register_date',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function miniOrders()
    {
        return $this->hasMany(MiniOrder::class);
    }

    // total_amount
    public function getTotalMoneySpentAttribute(){
        return $this->orders()->whereStatus(Order::STATUS_DELIVERED)->get()->sum('total_amount');
    }

    public function boughtProducts(){
        return Product::query()
        ->whereHas('orderItems.order',fn(Builder $query) => $query->whereCustomerId($this->id)->whereStatus(Order::STATUS_DELIVERED))->get();
    }

    public function getLastOnlineOrderDateAttribute(){
        $last_order = $this->orders()->where('status',Order::STATUS_DELIVERED)->latest('id')->value('created_at');
        return $last_order? (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($last_order):null;
    }

    public function getLastRealOrderDateAttribute(){
        $last_mini_order = $this->miniOrders()->latest('id')->value('created_at');
        return $last_mini_order? (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($last_mini_order):null;
    }

    public function getPersianRegisterDateAttribute(){
        return (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($this->created_at);
    }
}