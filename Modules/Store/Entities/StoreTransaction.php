<?php

namespace Modules\Store\Entities;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Entities\HasCommonRelations;
use Modules\Core\Entities\HasFilters;
use Modules\Core\Traits\HasMorphAuthors;
use Modules\Order\Entities\Order;
use Shetabit\Shopit\Modules\Store\Entities\StoreTransaction as BaseStoreTransaction;
use Spatie\Activitylog\Traits\LogsActivity;

class StoreTransaction extends BaseStoreTransaction
{
    use HasFactory, HasCommonRelations, HasFilters, LogsActivity,HasMorphAuthors;

    public $with = ['creator'];

    protected $fillable = [
        'type',
        'description',
        'quantity',
        'mini_order_id',
        'order_id',
        'is_done'
    ];

    public const TRANSACTION_DECREMENT = 'decrement';
    public const TRANSACTION_INCREMENT = 'increment';

    public const TRANSACTION_TYPES = [
        StoreTransaction::TRANSACTION_DECREMENT, StoreTransaction::TRANSACTION_INCREMENT
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function scopeOnlineOrder(
        Builder $query,
        $transactionType = StoreTransaction::TRANSACTION_DECREMENT,
        $excluded_order_statuses = ['delivered']
    ) {
        return $query->whereHas('order', function (Builder $order_query) use ($excluded_order_statuses) {
            $order_query->whereNotIn('status', $excluded_order_statuses);
        })
            ->whereType($transactionType);
    }

    public function scopeDoneState(Builder $transaction_query,$preferred_done_state){
        return $transaction_query->when(!$preferred_done_state,function(Builder $query) use ($transaction_query){
             $transaction_query->where('is_done',false)->orWhereNull('is_done');
        },$transaction_query->where('is_done',$preferred_done_state));

    }


}
