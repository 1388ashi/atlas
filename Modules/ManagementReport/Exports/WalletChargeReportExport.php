<?php

namespace Modules\ManagementReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class WalletChargeReportExport implements FromCollection
{
    public function __construct(protected $array){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'ردیف',
            'موبایل',
            'نام و نام خانوادگی',
            'مبلغ شارژ',
            'تاریخ',
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
