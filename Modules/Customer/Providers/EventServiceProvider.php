<?php

namespace Modules\Customer\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Customer\Listeners\AsignVipRoleBasedOnCustomerMoneySpent;
use Modules\Order\Events\OrderDelivered;

class EventServiceProvider extends ServiceProvider
{
     /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        OrderDelivered::class => [
            AsignVipRoleBasedOnCustomerMoneySpent::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
