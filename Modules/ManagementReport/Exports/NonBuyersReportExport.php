<?php

namespace Modules\ManagementReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class NonBuyersReportExport implements FromCollection
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
            'استان',
            'شهر',
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
