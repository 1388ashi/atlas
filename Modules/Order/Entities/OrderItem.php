<?php

namespace Modules\Order\Entities;

use Modules\Core\Helpers\Helpers;
use Illuminate\Database\Eloquent\Builder;
use Modules\Order\Services\Statuses\ChangeStatus;
use Shetabit\Shopit\Modules\Order\Entities\OrderItem as BaseOrderItem;

class OrderItem extends BaseOrderItem
{
    protected $fillable = [
        'product_id',
        'variety_id',
        'quantity',
        'amount',
        'status',
        'discount_amount',
        'extra',
        'flash_id',
        'is_done'
    ];

    public function scopeDoneState(Builder $orderItem_query,$preferred_done_state){
        return $orderItem_query->when(!$preferred_done_state,function(Builder $query) use ($orderItem_query){
             $orderItem_query->where('is_done',false)->orWhereNull('is_done');
        },$orderItem_query->where('is_done',$preferred_done_state));
    
    }

    public function scopePendingOrderItems(Builder $query) : Builder
    {
        $request = request();
        Helpers::toCarbonRequest(['start_date', 'end_date'], $request);

        return $query
        ->whereHas('order', function ($order_query) {
            $order_query->whereNotIn('status', array_merge(['delivered'],ChangeStatus::FAILED_STATUS));
        })
        ->where(function ($q) {
            $q->where('is_done', false)->orWhereNull('is_done');
        })
        ->when($request->filled('start_date'),function(Builder $q) use ($request){
            $q->where('order_items.created_at', '>', $request->start_date);
        })
        ->when($request->filled('end_date'),function(Builder $q) use ($request){
            $q->where('order_items.created_at', '<', $request->end_date);
        });
    }

    public function scopeDoneOrderItems(Builder $query): Builder
    {
        $request = request();
        Helpers::toCarbonRequest(['start_date', 'end_date'], $request);

        return $query
        ->whereHas('order', function ($order_query) {
            $order_query->whereNotIn('status', array_merge(['delivered'],ChangeStatus::FAILED_STATUS));
        })
        ->where(function ($q) {
            $q->where('is_done', true);
        })
        ->when($request->filled('start_date'),function(Builder $q) use ($request){
            $q->where('order_items.created_at', '>', $request->start_date);
        })
        ->when($request->filled('end_date'),function(Builder $q) use ($request){
            $q->where('order_items.created_at', '<', $request->end_date);
        });
    }
}
