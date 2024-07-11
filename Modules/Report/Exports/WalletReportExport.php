<?php

namespace Modules\Report\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class WalletReportExport implements FromCollection
{
    public function __construct(protected $array){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'شناسه',
            'نام و نام خانوادگی',
            'موبایل',
            'موجودی',
            'دریافت',
            'برداشت',
            'تعداد دریافت',
            'تعداد برداشت'
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
