<?php

namespace Modules\ManagementReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class BuyersReportExport implements FromCollection
{
    public function __construct(protected $array){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'ردیف',
            'موبایل',
            'نام و نام خانوادگی',
            'تاریخ آخرین سفارش',
            'مبلغ سفارش',
            'تعداد محصولات',
            'تعداد اقلام',
            'استان',
            'شهرستان'
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
