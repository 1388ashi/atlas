<?php

namespace Modules\ManagementReport\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\ManagementReport\Exports\CategorizedReportExport;
use Modules\ManagementReport\Http\Requests\CategorizedReportRequest;

class CategorizedReportController extends Controller
{
    public function makeReport(CategorizedReportRequest $request)
    {
        $categorized_report = $this->getReport($request);

        // در صورتی که نوع گزارش درخواستی، جنسیت باشد، عناوین تعیین می گردد
        if ($request->type == 'gender'){
            foreach ($categorized_report as $item) {
                $item->gender = match ($item->gender){
                    'male' => 'مرد',
                    'female' => 'زن',
                    default => 'تعیین نشده'
                };
            }
        }

        if (\request()->header('accept') == 'x-xlsx') {
            $final_list = [];
            $index = 1;
            $count_orders = 0;
            $sum_items_count = 0;
            $sum_items_quantity = 0;
            $sum_total_amount = 0;
            $sum_discount_amount = 0;

            foreach ($categorized_report as $item){
                $final_list [] = [
                    $index,
                    $item->{$request->type},
                    $item->count_orders,
                    $item->sum_items_count,
                    $item->sum_items_quantity,
                    $item->sum_total_amount,
                    $item->sum_discount_amount,
                ];
                $index++;
                $count_orders += $item->count_orders;
                $sum_items_count += $item->sum_items_count;
                $sum_items_quantity += $item->sum_items_quantity;
                $sum_total_amount += $item->sum_total_amount;
                $sum_discount_amount += $item->sum_discount_amount;
            }

            $final_list[] = [
                'جمع کل',
                '-',
                $count_orders,
                $sum_items_count,
                $sum_items_quantity,
                $sum_total_amount,
                $sum_discount_amount,
            ];
            $extra_title = match ($request->type){
                'province' => 'استان',
                'city' => 'شهرستان',
                'gender' => 'جنسیت',
            };
            return Excel::download(new CategorizedReportExport($final_list, $extra_title),
                __FUNCTION__ . '-' . now()->toDateString() . '.xlsx');
        }

        return response()->success('گزارش تفکیکی', compact('categorized_report'));
    }

    public function getReport($request)
    {
        return DB::table('orders as o')
            ->whereNull('o.reserved_id')
            ->whereIn('o.status',(new \Modules\Core\Helpers\Helpers)->getStatusesForReport())
            ->when($request->type == 'province',function($query) use ($request) {
                $query->select(
                    'province',
                    DB::raw('COUNT(*) count_orders'),
                    DB::raw('SUM(total_amount) as sum_total_amount'),
                    DB::raw('SUM(items_count) as sum_items_count'),
                    DB::raw('SUM(items_quantity) as sum_items_quantity'),
                    DB::raw('SUM(discount_amount) as sum_discount_amount'),
                );
                $query->groupBy('province');
                $query->orderBy(DB::raw('sum_total_amount'),'desc');
            })
            ->when($request->type == 'city',function($query) use ($request) {
                $query->where('province',$request->province);
                $query->select(
                    'city',
                    DB::raw('COUNT(*) count_orders'),
                    DB::raw('SUM(total_amount) as sum_total_amount'),
                    DB::raw('SUM(items_count) as sum_items_count'),
                    DB::raw('SUM(items_quantity) as sum_items_quantity'),
                    DB::raw('SUM(discount_amount) as sum_discount_amount'),
                );
                $query->groupBy('city');
                $query->orderBy(DB::raw('sum_total_amount'),'desc');
            })
            ->when($request->type == 'gender',function($query) use ($request) {
                $query->join('customers as c','o.customer_id','=','c.id');
                $query->select(
                    'c.gender',
                    DB::raw('COUNT(*) count_orders'),
                    DB::raw('SUM(total_amount) as sum_total_amount'),
                    DB::raw('SUM(items_count) as sum_items_count'),
                    DB::raw('SUM(items_quantity) as sum_items_quantity'),
                    DB::raw('SUM(discount_amount) as sum_discount_amount'),
                );
                $query->groupBy('c.gender');
                $query->orderBy(DB::raw('sum_total_amount'),'desc');
            })
            ->whereDate('o.created_at','>=',$request->start_date)
            ->whereDate('o.created_at','<=',$request->end_date)
            ->get();
    }
}
