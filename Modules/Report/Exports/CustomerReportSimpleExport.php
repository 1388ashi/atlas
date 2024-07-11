<?php

namespace Modules\Report\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class CustomerReportSimpleExport implements FromCollection
{

    public function __construct(protected $array){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'شناسه مشتری',
            'نام و نام خانوادگی',
            'موبایل',
            'ایمیل',
            'تاریخ ثبت نام',
            'تاریخ اخرین سفارش آنلاین',
            'تاریخ اخرین سفارش حضوری',
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
