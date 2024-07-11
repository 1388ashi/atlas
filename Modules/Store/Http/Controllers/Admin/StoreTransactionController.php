<?php

namespace Modules\Store\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Core\Helpers\Helpers;
use Illuminate\Support\Facades\DB;
use Modules\Product\Entities\Product;
use Illuminate\Database\Eloquent\Builder;
use Modules\Store\Entities\StoreTransaction;
use Modules\Core\Entities\BaseEloquentBuilder;
use Modules\Product\Entities\Variety;
use Shetabit\Shopit\Modules\Store\Http\Controllers\Admin\StoreTransactionController as BaseStoreTransactionController;

class StoreTransactionController extends BaseStoreTransactionController
{
    public function pending_list(Request $request): JsonResponse
    {
        if($request->has('is_done')){
            $request->merge([
                'is_done' => ($request->is_done === 'true') ? 1 : 0
            ]);
        }

           $varieties = Variety::query()
           ->withCommonRelations();
           if($request->input('is_done')){
                $varieties->whereHas('doneStoreTransactions')->with(['doneStoreTransactions']);
           }
           else {
                $varieties->whereHas('pendingStoreTransactions')->with(['pendingStoreTransactions']);
           }

           $varieties = $varieties->paginateOrAll(50);
        // $storeTransactions = $storeTransactions->latest()->filters()->groupBy('store_id')->select('*')->addSelect(DB::raw('sum(quantity) as variety_count'))->paginateOrAll(50);
       

        return response()->success('لیست تراکنش های انبار', compact('varieties'));
    }

    public function markAsDone(Request $request, StoreTransaction $transaction)
    {
        $transaction->update([
            'is_done' => true
        ]);
        $transaction->refresh();

        return response()->success('status changed successfully!', [
            'data' => $transaction
        ]);
    }

    public function markAsDoneBatch(Request $request)
    {
        StoreTransaction::whereIn('id', $request->input('transactions'))
            ->update([
                'is_done' => true
            ]);

        return response()->success('statuses changed successfully!', [
            'data' => StoreTransaction::whereIn('id', $request->input('transactions'))->get()
        ]);
    }
}
