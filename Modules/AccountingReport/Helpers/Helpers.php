<?php

namespace Modules\AccountingReport\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\AccountingReport\Http\Controllers\ProductSellReportController;
use Modules\Report\Entities\SellType;

class Helpers
{
    // حالت های موردنیاز جهت محاسبه
    function getStatusesForReport(): array
    {
        return ['new','delivered', 'in_progress','reserved'];
    }

    public function getGateways()
    {
        return [
            ['key' => 'behpardakht', 'value' => 'به پراخت ملت'],
            ['key' => 'virtual', 'value' => 'مجازی'],
            ['key' => 'sadad', 'value' => 'سداد'],
            ['key' => 'zarinpal', 'value' => 'زرین پال'],
            ['key' => 'saman', 'value' => 'سامان'],
            ['key' => 'pasargad', 'value' => 'پاسارگاد'],
            ['key' => 'irankish', 'value' => 'ایران کیش'],
            ['key' => 'total', 'value' => 'مجموع'],
        ];
    }

    public function applyFiltersToTotal($totals,$request,$use_join=false){
        $start_date = str_replace("+"," ",$request->start_date);
        $end_date = str_replace("+"," ",$request->end_date);
        $total_lower = $request->total_lower;
        $total_higher = $request->total_higher;
        $items_count_lower = $request->items_count_lower;
        $items_count_higher = $request->items_count_higher;
        $customer_id = $request->customer_id;

        if ($start_date){
            $field = $use_join?'o.created_at':'created_at';
            $d = explode(" ",$start_date);
            $d[1] = array_key_exists(1,$d) ? $d[1] .= ":00" : '00:00:00';
            $start_date = implode(" ",$d);
            $totals = $totals->where($field,'>=',$start_date);
//            Log::info('start_time: '.$start_date);
        }
        if ($end_date){
            $field = $use_join?'o.created_at':'created_at';
            $d = explode(" ",$end_date);
            $d[1] = array_key_exists(1,$d) ? $d[1] .= ":59" : '23:59:59';
            $end_date = implode(" ",$d);
            $totals = $totals->where($field,'<=',$end_date);
//            Log::info('end_time: '.$end_date);
        }
        if ($total_lower){
            $totals = $totals->where('total_amount','>=',$total_lower);
        }
        if ($total_higher){
            $totals = $totals->where('total_amount','<=',$total_higher);
        }
        if ($items_count_lower){
            $totals = $totals->where('items_quantity','>=',$items_count_lower);
        }
        if ($items_count_higher){
            $totals = $totals->where('items_quantity','<=',$items_count_higher);
        }
        if ($customer_id){
            $totals = $totals->where('customer_id',$customer_id);
        }

        return $totals;
    }

    // مجموع هزینه کالا
    public function getTotalAmount($date=null,$request=null,$table='orders'){
        $totals = DB::table($table)
            ->when($table=='orders',
                function($query) {
                    $query->select('status',DB::raw('sum(total_amount) as total'))
                        ->whereIn('status',$this->getStatusesForReport())
                        ->whereNull('reserved_id');
                },
                function ($query){
                    $query->select(DB::raw('sum(total_amount_sell) as total'));
//                        ->whereIn('type',['sell','both']);
                })
            ;

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        if ($table == 'orders'){
            // Log::info('orders query = ' . $totals->groupBy('status')->toSql());
            $totals = $totals->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $sum = 0;
            $statuses_to_sum = $this->getStatusesForReport();
            foreach ($statuses_to_sum as $key) {
                $sum += $totals[$key]??0;
            }
            return $sum;
        } else {
//            Log::info('mini orders query = ' . $totals->toSql());
            $sum = $totals->value('total');
            return (integer)$sum;
        }
    }

    // مجموع مبلغ مرجوعی
    public function getTotalRefundAmount($date=null,$request=null){
        $refund = (new \Modules\AccountingReport\Http\Controllers\ProductSellReportController)->calculateMiniOrdersRefund($request);
        $keys_refund = [
            'count_refund_total',
            'total_product_refund',
            'total_product_refund_discount'
        ];
        foreach (SellType::all() as $sell_type) {
            $keys_refund[] = "count_refund_".$sell_type->key;
        }
        $refund_totals = [];
        foreach ($keys_refund as $key) {
            $refund_totals[$key] = 0;
        }
        foreach ($refund as $refund_item) {
            foreach ($keys_refund as $key) {
                $refund_totals[$key] += $refund_item->$key;
            }
        }
//        Log::info("refund", $refund_totals);
        return $refund_totals;
    }

    // مجموع هزینه ارسال
    public function getTotalShippingAmount($date=null,$request=null,$table='orders'){
        $totals = DB::table($table)
            ->select('status',DB::raw('sum(shipping_amount) as total'))
            ->whereIn('status',$this->getStatusesForReport())
            ->whereNull('reserved_id');

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $totals = $totals->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $sum = 0;
        $statuses_to_sum = $this->getStatusesForReport();
        foreach ($statuses_to_sum as $key) {
            $sum += $totals[$key]??0;
        }

        return $sum;
    }

    // تعداد سفارش‌ها
    public function getTotalOrders($date=null,$request=null,$table='orders'){

        $totals = DB::table($table)
            ->when($table=='orders',
                function($query) {
                    $query->select('status',DB::raw('count(id) as count'))
                        ->whereIn('status',$this->getStatusesForReport())
                        ->whereNull('reserved_id');
                },
                function ($query){
                    $query->select(DB::raw('count(id) as count'))
                        ->whereIn('type',['sell','both']);
                })
            ;

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }
        if ($table == 'orders'){
            $totals = $totals->groupBy('status')
                ->pluck('count', 'status')
                ->toArray();

            $sum = 0;
            $statuses_to_sum = $this->getStatusesForReport();
            foreach ($statuses_to_sum as $key) {
                $sum += $totals[$key]??0;
            }

            return $sum;
        } else {
            $sum = $totals->value('count');
            return (integer)$sum;
        }

    }

    // تعداد اقلام
    public function getTotalOrderItems($date=null,$request=null,$table='orders'){
        $totals = DB::table($table)
            ->when($table=='orders',
                function($query) {
                    $query->select('status',DB::raw('sum(items_quantity) as total'))
                        ->whereNull('reserved_id');
                },
                function ($query){
                    $query->select(DB::raw('sum(items_quantity) as total'));
                })
            ;

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        if ($table == 'orders'){
            $totals = $totals->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $sum = 0;
            $statuses_to_sum = $this->getStatusesForReport();
            foreach ($statuses_to_sum as $key) {
                $sum += $totals[$key]??0;
            }

            return $sum;
        } else {
            $sum = $totals->value('total');
            return (integer)$sum;
        }

    }

    // تعداد اقلام مرجوعی
   public function getTotalOrderItemsRefund($date=null,$request=null,$table='orders'){
        $totals = DB::table($table)
            ->when($table=='orders',
                function($query) {
                    $query->select('status',DB::raw('sum(items_quantity) as total'))
                        ->whereNull('reserved_id');
                },
                function ($query){
                    $query->select(DB::raw('sum(items_quantity_refund) as total'));
                })
            ;

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        if ($table == 'orders'){
            $totals = $totals->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $sum = 0;
            $statuses_to_sum = $this->getStatusesForReport();
            foreach ($statuses_to_sum as $key) {
                $sum += $totals[$key]??0;
            }

            return $sum;
        } else {
            $sum = $totals->value('total');
            return (integer)$sum;
        }

    }

    // مجموع فروش (درآمد)
    public function getTotalIncome($date=null,$request=null,$table='orders'){
        $totalAmount = $this->getTotalAmount($date,$request,$table);
        $totalDiscountAmount = $this->getTotalDiscountAmount($date,$request,$table);
        return $totalAmount - $totalDiscountAmount;
    }

    // مجموع تخفیف
    public function getTotalDiscountAmount($date=null,$request=null,$table='orders'){
        $discountWithCoupon = $this->getTotalDiscountAmountWithCoupon($date,$request,$table);
        $discountWithoutCoupon = $this->getTotalDiscountAmountWithoutCoupon($date,$request,$table);
        return $discountWithCoupon + $discountWithoutCoupon;
    }

    // تخفیف با کوپن
    public function getTotalDiscountAmountWithCoupon($date=null,$request=null,$table='orders'){

        $totals = DB::table($table)
            ->select('status',DB::raw('sum(discount_amount) as total'))
            ->whereIn('status',$this->getStatusesForReport())
            ->whereNotNull('coupon_id')
            ->whereNull('reserved_id');

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $totals = $totals->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $sum = 0;
        $statuses_to_sum = $this->getStatusesForReport();
        foreach ($statuses_to_sum as $key) {
            $sum += $totals[$key]??0;
        }

        return $sum;
    }

    // تخفیف بدون کوپن
    public function getTotalDiscountAmountWithoutCoupon($date=null,$request=null,$table='orders'){

        $totals = DB::table($table)
            ->select('status',DB::raw('sum(discount_amount) as total'))
            ->whereIn('status',$this->getStatusesForReport())
            ->whereNull('coupon_id')
            ->whereNull('reserved_id');

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $totals = $totals->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $sum = 0;
        $statuses_to_sum = $this->getStatusesForReport();
        foreach ($statuses_to_sum as $key) {
            $sum += $totals[$key]??0;
        }

        return $sum;
    }

    public function getTotalCashierDiscountAmount($date=null,$request=null){

        $totals = DB::table('mini_orders')->select(DB::raw('sum(discount_amount) as total'));

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $sum = $totals->value('total');
        return (integer)$sum;
    }

    public function getTotalPaidByCash($date=null,$request=null){

        $totals = DB::table('mini_orders')->select(DB::raw('sum(paid_by_cash) as total'));

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $sum = $totals->value('total');
        return (integer)$sum;
    }

    public function getTotalPaidByCardToCard($date=null,$request=null){

        $totals = DB::table('mini_orders')->select(DB::raw('sum(paid_by_card_to_card) as total'));

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $sum = $totals->value('total');
        return (integer)$sum;
    }

    // خرید با اعتبار کیف پول
    public function getTotalPaidFromWallet($date=null,$request=null,$table='orders'){
        $totals = DB::table($table)
            ->select('status',DB::raw('sum(used_wallet_amount) as total'))
            ->whereIn('status',$this->getStatusesForReport())
            ->whereNull('reserved_id');

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request);
        }

        $totals = $totals->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $sum = 0;
        $statuses_to_sum = $this->getStatusesForReport();
        foreach ($statuses_to_sum as $key) {
            $sum += $totals[$key]??0;
        }

        return $sum;
    }


    // شارژ کیف پول
    public function getTotalWalletDeposit($date=null,$request=null){
        $start_date = $request->start_date??null;
        $end_date = $request->end_date??null;

        $totals = DB::table('invoices')
            ->select(DB::raw('sum(amount) as total'))
            ->where('status',"success")
            ->where('payable_type',"Modules\Customer\Entities\Deposit");

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            if ($start_date){
                $d = explode(" ",$start_date);
                $d[1] = array_key_exists(1,$d) ? $d[1] .= ":00" : '00:00:00';
                $start_date = implode(" ",$d);
                $totals = $totals->where('created_at','>=',$start_date);
            }
            if ($end_date){
                $d = explode(" ",$end_date);
                $d[1] = array_key_exists(1,$d) ? $d[1] .= ":59" : '23:59:59';
                $end_date = implode(" ",$d);
                $totals = $totals->where('created_at','<=',$end_date);
            }
        }

        return (int)$totals->first()->total;
    }


    // مجموع تراکنش های ورودی
    public function getTotalTransaction($date=null, $request=null, $type='deposit', $chargeType=null){
        $start_date = $request->start_date??null;
        $end_date = $request->end_date??null;

        $totals = DB::table('transactions')
            ->select(DB::raw('sum(amount) as total'))
            ->where('confirmed',1)
            ->where('payable_type',"Modules\Customer\Entities\Customer")
            ->when($chargeType == 'charge', function ($query) {
                $query->whereNull('meta'); // افزایش هایی که شارژ هستند
            })
            ->when($chargeType == 'not_charge', function ($query) {
                $query->whereNotNull('meta'); // افزایش هایی که شارژ نیستند
            })
            ->where('type',$type);

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            if ($start_date){
                $d = explode(" ",$start_date);
                $d[1] = array_key_exists(1,$d) ? $d[1] .= ":00" : '00:00:00';
                $start_date = implode(" ",$d);
                $totals = $totals->where('created_at','>=',$start_date);
            }
            if ($end_date){
                $d = explode(" ",$end_date);
                $d[1] = array_key_exists(1,$d) ? $d[1] .= ":59" : '23:59:59';
                $end_date = implode(" ",$d);
                $totals = $totals->where('created_at','<=',$end_date);
            }
        }

        return $totals->value('total');
    }

    // موجودی کیف پول
    public function getTotalWallet($date=null, $request=null){
        $start_date = $request->start_date??null;
        $end_date = $request->end_date??null;

        $totals = DB::table('transactions')
            ->select('type',DB::raw('sum(amount) as total'))
            ->where('confirmed',1)
            ->where('payable_type',"Modules\Customer\Entities\Customer")
            ->groupBy('type');

        if ($date){
            $totals = $totals->whereDate('created_at',$date);
        }
        if ($request && $request->all()){
            if ($start_date){
                $d = explode(" ",$start_date);
                $d[1] = array_key_exists(1,$d) ? $d[1] .= ":00" : '00:00:00';
                $start_date = implode(" ",$d);
                $totals = $totals->where('created_at','>=',$start_date);
            }
            if ($end_date){
                $d = explode(" ",$end_date);
                $d[1] = array_key_exists(1,$d) ? $d[1] .= ":59" : '23:59:59';
                $end_date = implode(" ",$d);
                $totals = $totals->where('created_at','<=',$end_date);
            }
        }

        $result = array();
        foreach ($totals->get()->toArray() as $item) {
            $result[$item->type] = $item->total;
        }
        $deposit = array_key_exists('deposit',$result)?$result['deposit']:0;
        $withdraw = array_key_exists('withdraw',$result)?$result['withdraw']:0;
        return $deposit + $withdraw;
    }

    // پرداختی تفکیک شده درگاه ها
    public function getTotalGatewayPayments($date=null,$request=null){
        $totals = DB::table('orders as o')
            ->leftJoin('invoices as i','i.payable_id','=','o.id')
            ->leftJoin('payments as p','p.invoice_id','=','i.id')
            ->select('o.id','p.gateway',DB::raw('sum(total_payable_amount) as total'))
            ->whereIn('o.status',$this->getStatusesForReport())
//            ->whereNull('reserved_id')
            ->where('i.payable_type','Modules\Order\Entities\Order')
        ;

        if ($date){
            $totals = $totals->whereDate('o.created_at',$date);
        }
        if ($request && $request->all()){
            $totals = $this->applyFiltersToTotal($totals,$request,true);
        }

        $totals = $totals->groupBy('p.gateway')
            ->pluck('total', 'gateway')
            ->toArray();

        $total_gateways = 0;
        foreach ($totals as $total) {
            $total_gateways += $total;
        }
        $totals['total'] = $total_gateways;

        return $totals;
    }

    // شارژ تفکیک شده درگاه ها
    public function getTotalGatewayCharges($date=null,$request=null){
        $start_date = $request->start_date;
        $end_date = $request->end_date;

        $totals = DB::table('transactions as t')
            ->leftJoin('invoices as i','i.payable_id','=','t.id')
            ->leftJoin('payments as p','p.invoice_id','=','i.id')
            ->select('p.gateway',DB::raw('sum(t.amount) as total'))
            ->where('confirmed',1)
            ->where('t.payable_type',"Modules\Customer\Entities\Customer")
            ->whereNull('meta'); // افزایش هایی که شارژ هستند
        ;
        if ($start_date){
            $d = explode(" ",$start_date);
            $d[1] = array_key_exists(1,$d) ? $d[1] .= ":00" : '00:00:00';
            $start_date = implode(" ",$d);
            $totals = $totals->where('t.created_at','>=',$start_date);
        }
        if ($end_date){
            $d = explode(" ",$end_date);
            $d[1] = array_key_exists(1,$d) ? $d[1] .= ":59" : '23:59:59';
            $end_date = implode(" ",$d);
            $totals = $totals->where('t.created_at','<=',$end_date);
        }

        $totals = $totals->groupBy('p.gateway')
            ->pluck('total', 'gateway')
            ->toArray();

        $total_gateways = 0;
        foreach ($totals as $total) {
            $total_gateways += $total;
        }
        $totals['total'] = $total_gateways;

        return $totals;
    }
}
