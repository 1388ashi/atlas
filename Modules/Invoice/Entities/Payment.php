<?php

namespace Modules\Invoice\Entities;

use Shetabit\Shopit\Modules\Invoice\Entities\Payment as BasePayment;

class Payment extends BasePayment
{
    public static function getAvailableDriversForFront(): array
    {
        $frontDrivers = [];
        $drivers = config('invoice.drivers');
        foreach ($drivers as $driverName => $driverInfo) {
            if (!static::isDriverEnabled($driverName)) {
                continue;
            }
            $frontDrivers[] = [
                'name' => $driverName,
                'image' => url($driverInfo['image']),
                'label' => $driverInfo['label']
            ];
        }

        usort($frontDrivers, function ($d1,$d2) {
            return (static::getDriverOrder($d1['name']) < static::getDriverOrder($d2['name'])) ? -1 : 1;
        });

        return $frontDrivers;
    }
}
