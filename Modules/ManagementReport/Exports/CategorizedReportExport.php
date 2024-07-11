<?php

namespace Modules\ManagementReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class CategorizedReportExport implements FromCollection
{
    public function __construct(protected $array, protected $title){}

    public function collection()
    {
        $finalModels = [];
        $finalModels[] = [
            'ردیف',
            $this->title,
            'تعداد سفارشات',
            'تعداد محصولات',
            'تعداد اقلام',
            'مبلغ خرید',
            'مبلغ تخفیف',
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
