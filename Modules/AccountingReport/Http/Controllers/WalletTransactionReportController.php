<?php

namespace Modules\AccountingReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\AccountingReport\Http\Requests\WalletTransactionReportRequest;

class WalletTransactionReportController extends Controller
{
    public function makeReport(WalletTransactionReportRequest $request)
    {
        // محاسبه موارد موردنیاز برای گزارشات
        (new \Modules\Core\Helpers\Helpers)->updateChargeTypeOfTransactions();

        $report_deposit = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit',null); // مجموع تراکنش های ورودی
        $report_deposit_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit','charge'); // مجموع تراکنش های ورودی (شارژ کیف پول)
        $report_deposit_not_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'deposit','not_charge'); // مجموع تراکنش های ورودی (به غیر از شارژ)
        $report_withdraw = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,\Request(),'withdraw',null); // مجموع تراکنش های خروجی

        $total_deposit = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,null,'deposit',null); // مجموع تراکنش های ورودی
        $total_deposit_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,null,'deposit','charge'); // مجموع تراکنش های ورودی (شارژ کیف پول)
        $total_deposit_not_charge = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,null,'deposit','not_charge'); // مجموع تراکنش های ورودی (به غیر از شارژ)
        $total_withdraw = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalTransaction(null,null,'withdraw',null); // مجموع تراکنش های خروجی
        $total_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalWallet(null,null); // مجموع موجودی کیف پول
//        $total_paid_from_wallet = (new \Modules\AccountingReport\Helpers\Helpers)->getTotalPaidFromWallet(null,\Request()); // پرداخت از کیف پول

        $result = [
            'report_deposit' => (integer)$report_deposit,
            'report_deposit_charge' => (integer)$report_deposit_charge,
            'report_deposit_not_charge' => (integer)$report_deposit_not_charge,
            'report_withdraw' => (integer)$report_withdraw,

            'total_deposit' => (integer)$total_deposit,
            'total_deposit_charge' => (integer)$total_deposit_charge,
            'total_deposit_not_charge' => (integer)$total_deposit_not_charge,
            'total_withdraw' => (integer)$total_withdraw,
            'total_wallet' => (integer)$total_wallet,
        ];

        return response()->success('گزارش کیف پول',compact('result'));
    }
}
