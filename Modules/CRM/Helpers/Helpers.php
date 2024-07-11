<?php

namespace Modules\CRM\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Helpers
{
    public function getDateFromDateTimeString($dateString)
    {
        $dateTime = Carbon::parse($dateString);

        // You can then use Carbon's methods to manipulate the date, for example:
        $year = $dateTime->year;
        $month = $dateTime->month;
        $day = $dateTime->day;

        // You can also format the date as needed:
        $formattedDate = $dateTime->format('Y-m-d');

        return $formattedDate;
    }

    // اطلاعات پروفایل مشتری
    public function getProfileData($customer_id)
    {
        $customer = DB::table('customers')->find($customer_id);
        return [
            'customer_id' => $customer_id,
            'mobile' => $customer->mobile,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'gender' => $customer->gender,
            'register_date' => $this->getDateFromDateTimeString($customer->created_at),
            'birth_date' => $customer->birth_date,
            'wallet_amount' => $this->getWalletAmount($customer_id),
            'orders_count' => $this->getTotalOrdersOfCustomer($customer_id),
            'delivered_items_count' => $this->getTotalDeliveredItemsCount($customer_id),
            'delivered_items_quantity' => $this->getTotalDeliveredItemsQuantity($customer_id),
            'used_discount_in_delivered_orders' => $this->getDiscountPercentageOnDeliveredOrders($customer_id),
            'last_delivered_order_date' => $this->getDateFromDateTimeString($this->getLastDeliveredOrderDate($customer_id)),
            'last_delivered_order_date_for_humans' => Carbon::parse($this->getLastDeliveredOrderDate($customer_id))->diffForHumans(),
            'comments' => $this->getCustomerComments($customer_id),
            'orders' => $this->getCustomerOrders($customer_id),
            'mini_orders' => $this->getCustomerMiniOrders($customer_id),
            'transactions' => $this->getCustomerTransactions($customer_id),
            'payments' => $this->getCustomerPayments($customer_id),
            'gateways' => (new \Modules\AccountingReport\Helpers\Helpers)->getGateways(),
            'addresses' => $this->getCustomerAddresses($customer_id),
        ];
    }

    // جمع سفارشات مشتری
    public function getTotalOrdersOfCustomer($customer_id){
        return DB::table('orders')
            ->select('status',DB::raw('count(id) as count'))
//            ->whereIn('status', (new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->whereNull('parent_id')
            ->where('customer_id',$customer_id)
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    // درصد سفارشات دارای تخفیف مشتری
    public function getDiscountPercentageOnDeliveredOrders($customer_id){
        $orders = DB::table('orders')
            ->select(DB::raw('count(id) as count'))
            ->where('status', 'delivered')
            ->whereNull('parent_id')
            ->where('customer_id',$customer_id)
            ->groupBy('status');

        $discounted_orders = clone $orders;

        $result = [];
        $result['delivered_orders'] = $orders->value('count');
        $result['discounted_orders'] = $discounted_orders->where('discount_amount','>',0)->value('count');
        $result['percentage'] = round($result['discounted_orders'] / $result['delivered_orders'] * 100);

        return $result;
    }

    // تعداد محصولات سفارش داده شده مشتری
    public function getTotalDeliveredItemsCount($customer_id){
        return DB::table('orders')
            ->select(DB::raw('sum(items_count) as count'))
            ->where('status', 'delivered')
            ->whereNull('parent_id')
            ->where('customer_id',$customer_id)
            ->value('count');
    }

    // تعداد اقلام سفارش داده شده مشتری
    public function getTotalDeliveredItemsQuantity($customer_id){
        return DB::table('orders')
            ->select(DB::raw('sum(items_quantity) as count'))
            ->where('status', 'delivered')
            ->whereNull('parent_id')
            ->where('customer_id',$customer_id)
            ->value('count');
    }

    // آخرین خرید موفق مشتری
    public function getLastDeliveredOrderDate($customer_id){
        return DB::table('orders')
            ->select(DB::raw('max(created_at) as date'))
            ->where('status', 'delivered')
            ->whereNull('parent_id')
            ->where('customer_id',$customer_id)
            ->value('date');
    }

    // موجودی کیف پول مشتری
    public function getWalletAmount($customer_id){
        return DB::table('wallets')
            ->select(DB::raw('balance'))
            ->where('holder_type', 'Modules\Customer\Entities\Customer')
            ->where('holder_id',$customer_id)
            ->value('balance');
    }

    // نظرات ثبت شده مشتری
    public function getCustomerComments($customer_id){
        return DB::table('product_comments as pc')
            ->join('products as p','pc.product_id','=','p.id')
            ->where('pc.creator_id',$customer_id)
            ->select(
                'p.id as product_id',
                'p.title as product_title',
                'pc.title as comment_title',
                'pc.body as comment_body',
                'pc.rate',
                'pc.status',
            )
            ->get();
    }

    // سفارشات آنلاین ثبت شده مشتری
    public function getCustomerOrders($customer_id){
        return DB::table('orders')
            ->where('customer_id',$customer_id)
            ->whereNull('reserved_id')
            ->select(
                'id',
                'first_name',
                'last_name',
                'province',
                'city',
                'status',
                'status_detail',
                'description',
                'items_count',
                'items_quantity',
                'shipping_amount',
                'discount_amount',
                'total_amount',
                'total_payable_amount',
            )
            ->get();
    }

    // سفارشات حضوری ثبت شده مشتری
    public function getCustomerMiniOrders($customer_id){
        return DB::table('mini_orders')
            ->where('customer_id',$customer_id)
            ->select(
                'id',
                'description',
                'items_count',
                'items_quantity',
                'discount_amount',
                'total_amount',
            )
            ->get();
    }

    // تراکنش های مشتری
    public function getCustomerTransactions($customer_id){
        return DB::table('transactions as t')
            ->join('charge_types as ct','t.charge_type_id','=','ct.id')
            ->where('t.payable_id',$customer_id)
            ->where('t.payable_type','Modules\Customer\Entities\Customer')
            ->select(
                'type',
                'amount',
                'confirmed',
//                'meta',
                DB::raw("CAST(json_unquote(JSON_EXTRACT(meta, '$.description')) as CHAR) as description"),
                'ct.title',
            )
            ->get();
    }

    // پرداخت های مشتری
    public function getCustomerPayments($customer_id){
        return DB::table('orders as o')
            ->leftJoin('invoices as i','i.payable_id','=','o.id')
            ->leftJoin('payments as p','p.invoice_id','=','i.id')
            ->where('i.payable_type','Modules\Order\Entities\Order')
            ->where('o.customer_id',$customer_id)
            ->select(
                'o.id as order_id',
                'o.total_amount',
                'o.total_payable_amount',
                'o.status as order_status',
                'i.amount as invoice_amount',
                'i.status as invoice_status',
                'i.status_detail',
                'p.status as payment_status',
                'p.gateway',
                'p.created_at',
            )
            ->get();
    }

    // آدرس های ثبت شده مشتری
    public function getCustomerAddresses($customer_id){
        return DB::table('addresses')
            ->where('customer_id',$customer_id)
            ->join('cities','cities.id','=','addresses.city_id')
            ->join('provinces','provinces.id','=','cities.province_id')
            ->select(
//                'id',
//                'customer_id',
//                'city_id',
                'first_name',
                'last_name',
                'mobile',
                'address',
                'postal_code',
                'telephone',
                'cities.name as city',
                'provinces.name as province',
//                'latitude',
//                'longitude',
            )
            ->get();
    }
}
