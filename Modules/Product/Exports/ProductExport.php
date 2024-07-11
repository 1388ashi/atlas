<?php

namespace Modules\Product\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class ProductExport extends \Shetabit\Shopit\Modules\Product\Exports\ProductExport
{
    public function collection()
    {
        $data = [
            ['barcode', 'name', 'price', 'count', 'var_name']
        ];
        foreach ($this->product->varieties as $variety) {
            $varName = '';
            foreach ($variety->attributes as $attribute) {
                if ($attribute->name === 'tarh') {
                    $varName .= $attribute->pivot->value;
                }
            }
            foreach ($variety->attributes as $attribute) {
                if ($attribute->name === 'size') {
                    $varName .= '-' . $attribute->pivot->value;
                }
            }
            $varName .= ' '. ($variety->color ? $variety->color->name : '');

            if ($variety->store->balance) {
                $row = [
                    'barcode' => $variety->barcode,
                    'name' => $this->product->title,
                    'price' => number_format($variety->final_price['amount']) . 'تومان',
                    'count' => $variety->store->balance,
                    'var_name' => $varName
                ];
                $data[] = array_values($row);
            }

        }

        return collect($data);
    }
}
