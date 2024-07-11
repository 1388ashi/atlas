<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Modules\Core\Helpers\Helpers;
use Modules\ManagementReport\Http\Requests\CustomerRegistrationReportRequest;

class CustomerRegistrationReportController extends Controller
{
    public function makeReport(CustomerRegistrationReportRequest $request)
    {
        $type = $request->type;
        $offset_year = $request->offset_year;
        $month = (int)$request->month;

        $thisYear = (new Helpers)->getThisYearPersian();
        $year = $thisYear - $offset_year;

        $customer_registration = match ($type) {
            'week' => $this->getWeeklyReport(),
            'month' => $this->getMonthlyReport($year, $month),
            'year' => $this->getYearlyReport($year),
            default => "پارامتر نامعتبر",
        };

        return response()->success('ثبت نام کاربران منجر به خرید', compact('customer_registration'));
    }

    public function getWeeklyReport(){
        $firstDay = (new Helpers)->firstDayOfWeek();
        $columns = [];
        for ($i = 0 ; $i < 7 ; $i++){
            $date =  date("Y-m-d", strtotime($firstDay . " + $i days"));
            $columns[$date] = $this->getCustomerRegistrations($date,$date);
        }
        return $columns;
    }

    public function getMonthlyReport($year,$month){
        $firstDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/01");
        $length = (new Helpers)->getDaysOfMonth($year,$month);
        $columns = [];
        for ($i = 0 ; $i < $length ; $i++){
            $date =  date("Y-m-d", strtotime($firstDayOfMonth . " + $i days"));
            $columns[$date] = $this->getCustomerRegistrations($date,$date);
        }
        return $columns;
    }

    public function getYearlyReport($year){
        $columns = [];
        for ($month = 1 ; $month <= 12 ; $month++){
            $firstDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/01");
            $length = (new Helpers)->getDaysOfMonth($year,$month);
            $lastDayOfMonth = (new Helpers)->convertShamsiToMiladi("$year/$month/$length");
            $columns[$month] = $this->getCustomerRegistrations($firstDayOfMonth,$lastDayOfMonth);
        }
        return $columns;
    }


    public function getCustomerRegistrations($start_date, $end_date, $need_list = false)
    {
        // در صورت ارسال پارامتر سوم با مقدار true، علاوه بر تعداد، لیست ثبت نامی ها هم دریافت می شود
        $customerRegistrations = DB::table('customers as c')
            ->leftJoin('orders as o', function($join) use ($start_date,$end_date) {
                $join->on('c.id', '=', 'o.customer_id') // در صورتی که سفارشی در بازه تعیین شده داشته باشد، تاریخ سفارش نیز درنظر گرفته می شود
                ->whereDate('o.created_at','>=',$start_date)
                    ->whereDate('o.created_at','<=',$end_date);
            })
            ->whereDate('c.created_at','>=',$start_date)
            ->whereDate('c.created_at','<=',$end_date)
            ->select(
                'c.id as customer_id',
                DB::raw("CONCAT(COALESCE(c.first_name, ''),' ',COALESCE(c.last_name, ''),' (',mobile,')') as full_name"),
                'gender',
                'o.id as order_id',
            )
            ->orderBy('c.created_at','desc')
            ->groupBy('c.id')
            ->get()
            ->toArray();

        $count_customers = 0;
        $count_customers_with_order = 0;
        foreach ($customerRegistrations as $customerRegistration) {
            $count_customers++;
            if ($customerRegistration->order_id){
                $count_customers_with_order++;
            }
        }

        $result = [
            'count_customers' => $count_customers,
            'count_customers_with_order' => $count_customers_with_order,
        ];

        if ($need_list){
            $result['list'] = $customerRegistrations;
        }
        return $result;
    }
}
