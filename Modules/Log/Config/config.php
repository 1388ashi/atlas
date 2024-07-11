<?php

use Modules\Admin\Entities\Admin;
use Modules\Customer\Entities\Customer;
use Modules\Flash\Entities\Flash;
use Modules\Invoice\Entities\Invoice;
use Modules\Newsletters\Entities\Newsletters;
use Modules\Order\Entities\Order;
use Modules\Product\Entities\Product;
use Modules\ProductComment\Entities\ProductComment;
use Modules\Shipping\Entities\Shipping;
use Modules\Store\Entities\Store;

return [
    'name' => 'Log',
    'causedModels' => [
        Customer::class,
        Admin::class
    ],
    'models' => [
        Product::class,
        ProductComment::class,
        Customer::class,
        Store::class,
        Shipping::class,
        Order::class,
        Invoice::class,
        Flash::class,
        Newsletters::class,
        \Modules\Category\Entities\Category::class,
    ]
];
