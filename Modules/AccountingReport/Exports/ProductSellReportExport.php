<?php

namespace Modules\AccountingReport\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class ProductSellReportExport implements FromCollection
{
    public function __construct(protected $array, protected $type='محصول'){}

    public function collection()
    {
        $finalModels = [];

        $finalModels[] = [
            'ردیف',
            'کد '.$this->type,
            'عنوان '.$this->type,
            'تعداد فروش آنلاین',
            'تعداد فروش حضوری',
            'مجموع فروش',
            'مجموع مبلغ فروش',
            'مجموع مبلغ تخفیف',
        ];

        foreach ($this->array as $item) {
            $finalModels[] = $item;
        }

        $finalModels = collect([...$finalModels]);

        return $finalModels;
    }
}
