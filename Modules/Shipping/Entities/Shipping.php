<?php

namespace Modules\Shipping\Entities;

use Illuminate\Support\Facades\Auth;
use Modules\Area\Entities\City;
use Modules\Order\Entities\Order;
use Shetabit\Shopit\Modules\Shipping\Entities\Shipping as BaseShipping;

class Shipping extends BaseShipping
{
    protected $fillable = [
        'minimum_delay',
        'name',
        'default_price',
        'free_threshold',
        'order',
        'description',
        'status',
        'packet_size',
        'first_packet_size',
        'more_packet_price',
        'is_free'
    ];
    public function getPriceByReservation(\Modules\Area\Entities\City $city, int $orderAmount, $newQuantity, $customer, $addressId, int $except = null)
    {
        if ($newQuantity == 0) {
            throw new \LogicException('Total quantity should by not zero');
        }

        if ($this->free_threshold && $orderAmount >= $this->free_threshold) {
            return 0;
        }

        $orders = $customer->orders();
        $parentOrder = $orders
            ->where('address_id', $addressId)
            ->where('status', Order::STATUS_RESERVED)
            ->isReserved()
            ->latest()->first();

        $fromCustomer = $this->getForCustomerPrice(Auth::user());
        if ($fromCustomer !== false) {
            return $fromCustomer;
        }

        /** @var $parentOrder Order */
        if ($parentOrder) {
            $shippingPacketPrice = $parentOrder->shipping_packet_amount;
            $oldQuantity = $parentOrder->getTotalTotalQuantity();
            $oldShippingAmountPaid = $parentOrder->shipping_amount;

            return static::getPacketHelper($newQuantity,$this->packet_size,
                $shippingPacketPrice, $this->more_packet_price, $this->first_packet_size, $oldQuantity,$oldShippingAmountPaid);
        }

        $price = $this->getAreaPrice($city, $orderAmount);

        return static::getPacketHelper($newQuantity,$this->packet_size, $price, $this->more_packet_price, $this->first_packet_size);
    }

    public function getAreaPrice($city, $orderAmount)
    {
        $price = $this->attributes['default_price'];
        // برای رزور ها حد آستانه رایگان نداریم
        if ($this->free_threshold && $orderAmount >= $this->free_threshold) {
            return 0;
        }elseif ($shippableCity = $this->cities->where('id', $city->id)->first()) {
            $price = $shippableCity->pivot->price;
        } elseif ($shippableProvince = $this->provinces->where('id', $city->province_id)->first()) {
            $price = $shippableProvince->pivot->price;
        }
        return $price;
    }
}
