<?php

namespace Modules\ManagementReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class HighestCustomerOrderAmountReportExport implements FromCollection
{
    public function __construct(protected $array){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'ردیف',
            'شناسه مشتری',
            'موبایل',
            'نام و نام خانوادگی',
            'جمع مبلغ سفارشات',
            'تعداد سفارشات',
            'تعداد محصول',
            'تعداد اقلام',
            'استان',
            'شهر'
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
