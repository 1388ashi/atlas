<?php

return [
    \Modules\Admin\Entities\Admin::class => [
        'provinces' => [
            \Modules\Area\Http\Controllers\Admin\ProvinceController::class,
            'index'
        ],
        'cities' => [
            \Modules\Area\Http\Controllers\User\CityController::class,
            'index'
        ],
        'attributes' => [
            \Modules\Attribute\Entities\Attribute::class,
            'index'
        ],
        'specifications' => [
            \Modules\Specification\Entities\Specification::class,
            'index'
        ],
        'brands' => [
            \Modules\Brand\Http\Controllers\All\BrandController::class,
            'index'
        ],
        'linkables' => [
            \Modules\Link\Http\Controllers\Admin\LinkController::class,
            'create'
        ],
        'slider_groups' => [
            \Modules\Slider\Http\Controllers\Admin\SliderController::class,
            'groups'
        ],
        'post_categories' => [
            \Modules\Blog\Http\Controllers\Admin\PostCategoryController::class,
            'index'
        ],
        'categories' => [
            \Modules\Category\Http\Controllers\Admin\CategoryController::class,
            'indexLittle'
        ],
        'menus' => [
            \Modules\Menu\Http\Controllers\Admin\MenuItemController::class,
            'index'
        ],
        'roles' => [
            \Modules\Permission\Http\Controllers\Admin\RoleController::class,
            'index'
        ],
        'list_products' => [
            \Modules\Product\Http\Controllers\Admin\ProductController::class,
            'listProducts'
        ],
        'list_customers' => [
            \Modules\Customer\Http\Controllers\Admin\CustomerController::class,
            'listCustomers'
        ],
        'create_product' => [
            \Modules\Product\Http\Controllers\Admin\ProductController::class,
            'create'
        ],
        'create_customer' => [
            \Modules\Customer\Http\Controllers\Admin\CustomerRoleController::class,
            'index'
        ],
        'customer_roles' => [
            \Modules\Customer\Http\Controllers\Admin\CustomerRoleController::class,
            'index'
        ],
        'settings' => [
            \Modules\Setting\Http\Controllers\Admin\SettingController::class,
            'allSettings'
        ],
        'shippings' => [
            \Modules\Shipping\Http\Controllers\Customer\ShippingController::class,
            'index'
        ],
    ],
    \Modules\Customer\Entities\Customer::class => [
        'provinces' => [
            \Modules\Area\Http\Controllers\Admin\ProvinceController::class,
            'index'
        ],
        'cities' => [
            \Modules\Area\Http\Controllers\User\CityController::class,
            'index'
        ],
        'shippings' => [
            \Modules\Shipping\Http\Controllers\Customer\ShippingController::class,
            'index'
        ],
        'gateways' => [
            \Modules\Invoice\Http\Controllers\All\GatewayController::class,
            'index'
        ]
    ]
];
