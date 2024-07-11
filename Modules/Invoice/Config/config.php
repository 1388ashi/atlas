<?php

return [
    'name' => 'Invoice',
    'float' => false,
    'invoice_expire_time' => 3600, // Timestamp - 60 minutes
    'payment_expire_time' => 900, // Timestamp - 15 minutes
    'default_diver' => 'zarinpal',

    // Active drivers are defined at root/settings.php
    'drivers' => [
        'virtual' => [
            'model' => \Modules\Invoice\Drivers\VirtualDriver::class,
            'label' => 'مجازی',
            'image' => '/assets/images/drivers/virtual.png',
            'options' => []
        ],
        'sadad' => [
            'model' => \Modules\Invoice\Drivers\SadadDriver::class,
            'label' => 'بانک ملی',
            'image' => '/assets/images/drivers/melli.png',
            'options' => []
        ],
        'behpardakht' => [
            'model' => \Modules\Invoice\Drivers\BehpardakhtDriver::class,
            'label' => 'به پرداخت ملت',
            'image' => '/assets/images/drivers/mellat.png',
            'options' => []
        ],
        'saman' => [
            'model' => \Modules\Invoice\Drivers\SamanDriver::class,
            'label' => 'سامان',
            'image' => '/assets/images/drivers/sep.png',
            'options' => []
        ],
        'zarinpal' => [
            'model' => \Shetabit\Shopit\Modules\Invoice\Drivers\ZarinpalDriver::class,
            'label' => 'زرین پال',
            'image' => '/assets/images/drivers/zarinpal.png',
            'options' => []
        ],
        'parsian' => [
            'model' => \Shetabit\Shopit\Modules\Invoice\Drivers\ParsianDriver::class,
            'label' => 'پارسیان',
            'image' => '/assets/images/drivers/parsian.png',
            'options' => []
        ],
        'pasargad' => [
            'model' => \Modules\Invoice\Drivers\PasargadDriver::class,
            'label' => 'پاسارگاد',
            'image' => '/assets/images/drivers/pasargad.png',
            'options' => []
        ],
        'irankish' => [
            'model' => \Modules\Invoice\Drivers\IranKishDriver::class,
            'label' => 'ایران کیش',
            'image' => '/assets/images/drivers/irankish.png',
            'options' => []
        ],
    ]
];
