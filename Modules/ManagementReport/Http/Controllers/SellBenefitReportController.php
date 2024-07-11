<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Http\Requests\SellBenefitReportRequest;

class SellBenefitReportController extends Controller
{
    public function makeReport(SellBenefitReportRequest $request)
    {
        $type = $request->type;
        $offset_year = $request->offset_year;
        $month = (int)$request->month;

        $thisYear = (new Helpers)->getThisYearPersian();
        $year = $thisYear - $offset_year;

        $sell_benefit = match ($type) {
            'week' => $this->getWeeklyReport(),
            'month' => $this->getMonthlyReport($year, $month),
            'year' => $this->getYearlyReport($year),
            default => "پارامتر نامعتبر",
        };

        return response()->success('سود حاصل از فروش', compact('sell_benefit'));
    }

    public function getWeeklyReport(){
//        $firstDay = (new Helpers)->firstDayOfWeek();
        $firstDay = '2023-08-01';
        $columns = [];
        for ($i = 0 ; $i < 7 ; $i++){
            $date =  date("Y-m-d", strtotime($firstDay . " + $i days"));
            $columns[$date] = $this->getSellBenefit($date,$date);
        }
        return $columns;
    }

    public function getMonthlyReport($year,$month){
        $firstDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/01");
        $length = (new Helpers)->getDaysOfMonth($year,$month);
        $columns = [];
        for ($i = 0 ; $i < $length ; $i++){
            $date =  date("Y-m-d", strtotime($firstDayOfMonth . " + $i days"));
            $columns[$date] = $this->getSellBenefit($date,$date);
        }
        return $columns;
    }

    public function getYearlyReport($year){
        $columns = [];
        for ($month = 1 ; $month <= 12 ; $month++){
            $firstDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/01");
            $length = (new Helpers)->getDaysOfMonth($year,$month);
            $lastDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/$length");
            $columns[$month] = $this->getSellBenefit($firstDayOfMonth,$lastDayOfMonth);
        }
        return $columns;
    }

    public function getSellBenefit($start_date, $end_date)
    {
        // سود حاصل از فروش محصولاتی که قیمت خرید آن ها ثبت شده است
        return DB::table('order_items as oi')
            ->join('orders as o','o.id','=','oi.order_id')
            ->join('products as p','p.id','=','oi.product_id')
            ->whereNull('o.reserved_id')
            ->where('oi.status',1)
            ->whereIn('o.status',(new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->whereDate('o.created_at','>=',$start_date)
            ->whereDate('o.created_at','<=',$end_date)
            ->whereNotNull('p.purchase_price')
            ->select(
                DB::raw("sum(oi.amount * oi.quantity) as total_sell"),
                DB::raw("sum( ( CAST(oi.amount AS SIGNED) - CAST(p.purchase_price AS SIGNED) ) * CAST(oi.quantity AS SIGNED) ) as total_benefit"),
            )
            ->first();
    }
}
