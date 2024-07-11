<?php

namespace Modules\Report\Exports;

use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Shetabit\Shopit\Modules\Report\Exports\CustomerReportExport as BaseCustomerReportExport;

class CustomerReportExport extends BaseCustomerReportExport
{
    public function collection()
    {
        $finalModels = [];
        $finalModels[] = [
            'نام و نام خانوادگی',
            'شماره موبایل',
            'میزان خرید کل',
            'تاریخ اولین سفارش',
            'تاریخ اخرین سفارش',
            'نام شهر',
            'تعداد قلم های خریداری شده',
            'تعداد کل سفارشات',
        ];
        foreach ($this->models as $model) {
            $item = [];
            $m = $model->toArray();
            $item[] = $m['customer_name'];
            $item[] = $m['customer']['mobile'];
            $item[] = $m['_total'];
            $item[] = $m['first_order_date'];
            $item[] = $m['latest_order_date'];
            $item[] = $m['city_name'];
            $item[] = $m['order_items_count'];
            $item[] = $m['orders_count'];
            $finalModels[] = $item;
        }
        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
