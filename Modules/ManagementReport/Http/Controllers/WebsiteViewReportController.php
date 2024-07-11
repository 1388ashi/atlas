<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Http\Requests\WebsiteViewReportRequest;

class WebsiteViewReportController extends Controller
{
    public function makeReport(WebsiteViewReportRequest $request)
    {
        $type = $request->type;
        $date = $request->date;
        $offset_year = $request->offset_year;
        $month = (int)$request->month;

        $thisYear = (new Helpers)->getThisYearPersian();
        $year = $thisYear - $offset_year;

        $website_views = match ($type) {
            'day' => $this->getDailyReport($date),
            'week' => $this->getWeeklyReport(),
            'month' => $this->getMonthlyReport($year, $month),
            'year' => $this->getYearlyReport($year),
            default => "پارامتر نامعتبر",
        };

        return response()->success('بازدیدهای وب سایت', compact('website_views'));
    }

    public function getDailyReport($date)
    {
        // دریافت تعداد بازدیدهای روزانه وب سایت به تفکیک ساعت
        $site_views = DB::table('site_views as v')
            ->whereDate('date',$date)
            ->select(
                'hour',
                'count',
            )
            ->orderBy(DB::raw('hour'))
            ->get()
            ->toArray();
        $views = [];
        foreach ($site_views as $site_view) {
            $views[$site_view->hour] = $site_view->count;
        }
        return $views;
    }

    public function getWeeklyReport(){
        $firstDay = (new Helpers)->firstDayOfWeek();
//        $firstDay = '2023-08-01';
        $columns = [];
        for ($i = 0 ; $i < 7 ; $i++){
            $date =  date("Y-m-d", strtotime($firstDay . " + $i days"));
            $columns[$date] = $this->getWebsiteViews($date,$date);
        }
        return $columns;
    }

    public function getMonthlyReport($year,$month){
        $firstDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/01");
        $length = (new Helpers)->getDaysOfMonth($year,$month);
        $columns = [];
        for ($i = 0 ; $i < $length ; $i++){
            $date =  date("Y-m-d", strtotime($firstDayOfMonth . " + $i days"));
            $columns[$date] = $this->getWebsiteViews($date,$date);
        }
        return $columns;
    }

    public function getYearlyReport($year){
        $columns = [];
        for ($month = 1 ; $month <= 12 ; $month++){
            $firstDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/01");
            $length = (new Helpers)->getDaysOfMonth($year,$month);
            $lastDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/$length");
            $columns[$month] = $this->getWebsiteViews($firstDayOfMonth,$lastDayOfMonth);
        }
        return $columns;
    }

    public function getWebsiteViews($start_date, $end_date)
    {
        // دریافت تعداد بازدیدهای وب سایت
        return DB::table('site_views as v')
            ->whereDate('date','>=',$start_date)
            ->whereDate('date','<=',$end_date)
            ->select(
                DB::raw('SUM(count) AS view_count'),
            )
            ->orderBy(DB::raw('view_count'),'desc')
            ->value(DB::raw('view_count'));
    }
}
