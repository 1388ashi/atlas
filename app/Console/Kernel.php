<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Modules\Cart\Jobs\RemoveOldCartsJob;
use Modules\Order\Jobs\ChangeStatusToFailedJob;
use Shetabit\Shopit\Modules\Core\Console\Kernel as BaseKernel;
use Shetabit\Shopit\Modules\Order\Jobs\RemoveFailedOrdersJob;
use Shetabit\Shopit\Modules\Order\Jobs\ReservationTimeOutNotificationJob;
use Shetabit\Shopit\Modules\Product\Jobs\CheckDiscountUntilJob;

class Kernel extends BaseKernel {
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('shopit:sitemap')->everyThirtyMinutes();
        $schedule->job(ChangeStatusToFailedJob::class)->at('04:00:00');
        $schedule->job(ReservationTimeOutNotificationJob::class)->everyMinute();
        $schedule->job(CheckDiscountUntilJob::class)->everyMinute();
        $schedule->job(RemoveFailedOrdersJob::class)->everyMinute();
        $schedule->job(RemoveOldCartsJob::class)->hourly();
    }

}

