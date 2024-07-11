<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Exports\WalletChargeReportExport;
use Modules\ManagementReport\Http\Requests\WalletChargeReportRequest;

class WalletChargeReportController extends Controller
{
    public function makeReport(WalletChargeReportRequest $request)
    {
        $wallet_charges = $this->getTotalTransaction($request);

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            $total_deposit = 0;
            foreach ($wallet_charges->get() as $item){
                $persian_date_time = $item->created_at;
                $persian_date_time = explode(' ', $persian_date_time);
                $persian_date_time[0] = (new \Modules\Core\Helpers\Helpers)->convertMiladiToShamsi($persian_date_time[0]);
                $persian_date_time = implode(" ",$persian_date_time);
                $final_list [] = [
                    $index,
                    $item->mobile,
                    $item->full_name,
                    $item->amount,
                    $persian_date_time,
                ];
                $index++;
                $total_deposit += $item->amount;
            }

            $final_list[] = [
                'جمع کل',
                '-',
                '-',
                $total_deposit,
                '-',
            ];

            return Excel::download(new WalletChargeReportExport($final_list),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        $wallet_charges = $wallet_charges->paginate(20);

        return response()->success('شارژهای کیف پول', compact('wallet_charges'));
    }


    // مجموع تراکنش های ورودی
    public function getTotalTransaction($request, $type='deposit'){
        $start_date = $request->start_date??null;
        $end_date = $request->end_date??null;

        return DB::table('transactions as t')
            ->join('customers as c','t.payable_id','=','c.id')
            ->select(
                DB::raw("CONCAT(first_name, ' ', last_name) as full_name"),
                'mobile',
                't.amount',
                't.created_at'
            )
            ->where('confirmed',1)
            ->where('payable_type',"Modules\Customer\Entities\Customer")
            ->whereNull('meta') // افزایش هایی که شارژ هستند
            ->where('type','deposit')
            ->whereDate('t.created_at','>=',$start_date)
            ->whereDate('t.created_at','<=',$end_date);
    }
}
