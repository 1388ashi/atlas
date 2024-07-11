<?php

namespace Modules\ManagementReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class HighestOrderPriceReportExport implements FromCollection
{
    public function __construct(protected $array){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'ردیف',
            'شناسه سفارش',
            'شناسه مشتری',
            'موبایل',
            'نام و نام خانوادگی',
            'مبلغ سفارش',
            'تعداد محصول',
            'تعداد اقلام',
            'تاریخ سفارش',
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
