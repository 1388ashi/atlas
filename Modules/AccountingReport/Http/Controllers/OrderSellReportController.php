<?php

namespace Modules\AccountingReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\AccountingReport\Helpers\Helpers;
use Modules\AccountingReport\Http\Requests\OrderSellReportRequest;

class OrderSellReportController extends Controller
{
    public function makeReport(OrderSellReportRequest $request)
    {
        $order_totals = $this->calculateOrders($request);
        $mini_order_totals = $this->calculateMiniOrders($request);

        $merged_array = $order_totals;
        foreach ($mini_order_totals as $key => $mo) {
            if (array_key_exists($key,$merged_array)){
                $merged_array[$key] += $mo;
            } else {
                $merged_array[$key] = $mo;
            }
        }

        return response()->success('گزارش مالی سفارشات',compact('order_totals','mini_order_totals', 'merged_array'));
    }

    function calculateOrders($request){
        // در صورتی که گزارش مربوط به یک روز باشد اطلاعات محاسبه ای آن پاک شده و باعث می شود که دوباره محاسبه شوند
        if ($request->start_date == $request->end_date){
            DB::table('orders')
                ->whereDate('created_at',$request->start_date)
                ->update(['items_count' => null]);
        }

        // محاسبه موارد موردنیاز برای گزارشات
        (new \Modules\Core\Helpers\Helpers)->updateOrdersUsefulData();
        (new \Modules\Core\Helpers\Helpers)->updateOrdersCalculateData();
        (new \Modules\Core\Helpers\Helpers)->updateChargeTypeOfTransactions();

        $total_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalAmount(null,\Request()); // جمع کل فروش
//            $total_invoice_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalInvoiceAmount(null,\Request()),
//            $total_payable_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPayableAmount(null,\Request()),
//            $total_income = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalIncome(null,\Request()),
        $total_shipping_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalShippingAmount(null,\Request()); // هزینه ارسال
        $total_orders = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalOrders(null,\Request()); // جمع سفارشات
        $total_order_items = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalOrderItems(null,\Request()); // تعداد تنوع
        $total_discount_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalDiscountAmount(null,\Request()); // کل تخفیفات
        $total_discount_with_coupon = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalDiscountAmountWithCoupon(null,\Request()); // تخفیف با کد تخفیف
        $total_discount_without_coupon = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalDiscountAmountWithoutCoupon(null,\Request()); // تخفیف بدون کد تخفیف
//            $total_gift_wallet_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalGiftWalletAmount(null,\Request()),
        $total_paid_from_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPaidFromWallet(null,\Request()); // پرداخت از کیف پول
        $total_wallet_deposit = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalWalletDeposit(null,\Request()); // شارژ کیف پول
//            $total_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalWallet(null,\Request()),
//            $total_gift_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalGiftWallet(null,\Request()),
//            $total_deposit = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit',null),
//            $total_deposit_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit','charge'),
//            $total_deposit_not_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit','not_charge'),
//            $total_withdraw = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'withdraw',null),

        $total_paid_by_gateway = $total_amount + $total_shipping_amount + $total_wallet_deposit - $total_paid_from_wallet - $total_discount_amount; // جمع کل فروش از طریق درگاه پرداخت آنلاین

        return [
            'total_amount' => $total_amount, // جمع کل فروش
            'total_paid_by_gateway' => $total_paid_by_gateway, // جمع کل فروش از طریق درگاه پرداخت آنلاین
//            'total_invoice_amount' => $total_invoice_amount,
//            'total_payable_amount' => $total_payable_amount,
//            'total_income' => $total_income,
            'total_shipping_amount' => $total_shipping_amount, // هزینه ارسال
            'total_orders' => $total_orders, // جمع سفارشات
            'total_order_items' => $total_order_items, // تعداد تنوع
            'total_discount_amount' => $total_discount_amount, // کل تخفیفات
            'total_discount_with_coupon' => $total_discount_with_coupon, // تخفیف با کد تخفیف
            'total_discount_without_coupon' => $total_discount_without_coupon, // تخفیف بدون کد تخفیف
//            'total_gift_wallet_amount' => $total_gift_wallet_amount,
            'total_paid_from_wallet' => $total_paid_from_wallet, // پرداخت از کیف پول
            'total_wallet_deposit' => $total_wallet_deposit, // شارژ کیف پول
//            'total_wallet' => $total_wallet,
//            'total_gift_wallet' => $total_gift_wallet,
//            'total_deposit' => $total_deposit,
//            'total_deposit_charge' => $total_deposit_charge,
//            'total_deposit_not_charge' => $total_deposit_not_charge,
//            'total_withdraw' => $total_withdraw,
            'net_sales' => $total_amount - ($total_discount_amount ), // فروش خالص
        ];
    }

    function calculateMiniOrders($request)
    {
        // در صورتی که گزارش مربوط به یک روز باشد اطلاعات محاسبه ای آن پاک شده و باعث می شود که دوباره محاسبه شوند
        if ($request->start_date == $request->end_date) {
            DB::table('mini_orders')
                ->whereDate('created_at', $request->start_date)
                ->update(['items_count' => null]);
        }

        // محاسبه موارد موردنیاز برای گزارشات
        (new \Modules\Core\Helpers\Helpers)->updateMiniOrdersCalculateData();

        $total_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalAmount(null,\Request(),'mini_orders'); // جمع کل فروش
        $total_refund = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalRefundAmount(null,\Request()); // جمع کل مرجوعی
//            $total_invoice_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalInvoiceAmount(null,\Request(),'mini_orders');
//            $total_payable_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPayableAmount(null,\Request(),'mini_orders');
//            $total_income = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalIncome(null,\Request(),'mini_orders');
//            $total_shipping_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalShippingAmount(null,\Request(),'mini_orders'); // هزینه ارسال
            $total_orders = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalOrders(null,\Request(),'mini_orders'); // جمع سفارشات
            $total_order_items = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalOrderItems(null,\Request(),'mini_orders'); // تعداد تنوع
            $total_order_items_refund = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalOrderItemsRefund(null,\Request(),'mini_orders'); // تعداد تنوع
            $total_cashier_discount_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalCashierDiscountAmount(null,\Request()); // کل تخفیفات
//            $total_discount_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalDiscountAmount(null,\Request(),'mini_orders'); // کل تخفیفات
//            $total_discount_with_coupon = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalDiscountAmountWithCoupon(null,\Request(),'mini_orders'); // تخفیف با کد تخفیف
//            $total_discount_without_coupon = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalDiscountAmountWithoutCoupon(null,\Request(),'mini_orders'); // تخفیف بدون کد تخفیف
//            $total_gift_wallet_amount = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalGiftWalletAmount(null,\Request(),'mini_orders');
//            $total_paid_from_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPaidFromWallet(null,\Request(),'mini_orders'); // پرداخت از کیف پول
//            $total_wallet_deposit = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalWalletDeposit(null,\Request()), // شارژ کیف پول
//            $total_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalWallet(null,\Request(),'mini_orders');
//            $total_gift_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalGiftWallet(null,\Request(),'mini_orders');
//            $total_deposit = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit',null),
//            $total_deposit_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit','charge');
//            $total_deposit_not_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit','not_charge');
//            $total_withdraw = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'withdraw',null),
            $total_paid_by_cash = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPaidByCash(null,\Request()); // کل پرداختی های نقدی
            $total_paid_by_cart_to_card = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPaidByCardToCard(null,\Request()); // کل پرداختی های کارت به کارت
            $total_paid_by_pos = $total_amount - ($total_paid_by_cash + $total_paid_by_cart_to_card); // کل پرداختی های کارتخوان

        return [
            'total_amount' => $total_amount,
            'total_refund_amount' => $total_refund['total_product_refund'],
            'total_refund_items' => $total_refund['count_refund_real'],
//            'total_invoice_amount' => $total_invoice_amount,
//            'total_payable_amount' => $total_payable_amount,
//            'total_income' => $total_income,
//            'total_shipping_amount' => $total_shipping_amount,
            'total_orders' => $total_orders,
            'total_order_items' => $total_order_items,
            'total_order_items_refund' => $total_order_items_refund,
            'total_cashier_discount_amount' => $total_cashier_discount_amount,
//            'total_discount_amount' => $total_discount_amount,
//            'total_discount_with_coupon' => $total_discount_with_coupon,
//            'total_discount_without_coupon' => $total_discount_without_coupon,
//            'total_gift_wallet_amount' => $total_gift_wallet_amount,
//            'total_paid_from_wallet' => $total_paid_from_wallet,
//            'total_wallet_deposit' => $total_wallet_deposit,
//            'total_wallet' => $total_wallet,
//            'total_gift_wallet' => $total_gift_wallet,
//            'total_deposit' => $total_deposit,
//            'total_deposit_charge' => $total_deposit_charge,
//            'total_deposit_not_charge' => $total_deposit_not_charge,
//            'total_withdraw' => $total_withdraw,
            'total_paid_by_cash' => $total_paid_by_cash,
            'total_paid_by_cart_to_card' => $total_paid_by_cart_to_card,
            'total_paid_by_pos' => $total_paid_by_pos,
            'net_sales' => $total_amount - ($total_cashier_discount_amount + $total_refund['total_product_refund'] ), // فروش خالص
        ];
    }
}
