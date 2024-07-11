<?php

namespace Modules\Order\Imports;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToModel;
use Modules\Order\Entities\ShippingExcel;

class ShippingExcelImport implements ToModel
{
    public function model(array $row)
    {
        $row[2] = str_replace(' ', '', $row[2]);
        if (!is_numeric($row[2]) || ($row[2] <= 0)) {
            return null;
        }
        if (ShippingExcel::where('barcode', $row[2])->exists()) {
            return null;
        }

        return new ShippingExcel([
            'title' => $row[1], // B
            'barcode' => $row[2], // C
            'register_date' => $row[3], // D
            'special_services' => $row[4], // E
            'destination' => str_replace('ي', 'ی', $row[5]), // F
            'sender_name' => str_replace('ي', 'ی', $row[6]), // G
            'receiver_name' => str_replace('ي', 'ی', $row[8]), // I
            'price' => $row[13], // N
            'repository' => '',
            'reference_number' => '',
        ]);
    }
}
