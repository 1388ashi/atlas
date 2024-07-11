<?php

namespace Modules\Product\Entities;

use Illuminate\Database\Eloquent\Builder;
use Modules\Core\Helpers\Helpers;
use Modules\Store\Entities\Store;
use Modules\Core\Classes\DontAppend;
use Modules\Order\Entities\OrderItem;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Shetabit\Shopit\Modules\Product\Entities\Variety as BaseVariety;

class Variety extends BaseVariety
{
    protected $appends = ['unique_attributes_key', 'images', 'quantity', 'final_price', 'final_gifts','pending_for_exit_count','exited_count'];

    public function pendingOrderItems(): HasMany
    {
        $request = request();
        Helpers::toCarbonRequest(['start_date', 'end_date'], $request);

        return $this->hasMany(OrderItem::class)
        ->whereHas('order', function ($order_query) {
            $order_query->whereNotIn('status', ['delivered']);
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

    public function doneOrderItems(): HasManyThrough
    {
        $request = request();
        Helpers::toCarbonRequest(['start_date', 'end_date'], $request);

        return $this->hasMany(OrderItem::class)
        ->whereHas('order', function ($order_query) {
            $order_query->whereNotIn('status', ['delivered']);
        })
        ->where(function ($q) {
            $q->where('is_done', true);
        })
        ->when($request->filled('start_date'),function(Builder $q) use ($request){
            $q->where('order_items.updated_at', '>', $request->start_date);
        })
        ->when($request->filled('end_date'),function(Builder $q) use ($request){
            $q->where('order_items.updated_at', '<', $request->end_date);
        });
    }

    public function getPendingForExitCountAttribute() : int|DontAppend {
        return $this->relationLoaded('pendingOrderItems') ? $this->pendingOrderItems->sum('quantity') : new DontAppend('PendingForExitCount');
    }

    public function getExitedCountAttribute() : int|DontAppend {
        return $this->relationLoaded('doneOrderItems') ? $this->doneOrderItems->sum('quantity') : new DontAppend('PendingForExitCount');
    }

//    public static function calculateDiscount($model, int $price, string $name): array
//    {
//        $appliedDiscountType = $name;
//        if ($model->discount_type == static::DISCOUNT_TYPE_FLAT){
//            $appliedDiscountPrice = $model->discount;
//            $discountType =  $model->discount_type;
//        }else{
//            $appliedDiscountPrice = (int)round(($model->discount * $price) / 100);
//            $discountType =  static::DISCOUNT_TYPE_PERCENTAGE;
//        }
//        $finalPricePrice = $price;
//
//        return [
//            'discount_model'  => $appliedDiscountType,
//            'discount_type'  => $discountType,
//            'discount'  => $model->discount,
//            'discount_price' => $appliedDiscountPrice,
//            'amount'      => $finalPricePrice
//        ];
//    }

}
