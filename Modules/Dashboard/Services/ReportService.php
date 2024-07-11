<?php

namespace Modules\Dashboard\Services;

use Illuminate\Support\Carbon;
use Modules\Report\Entities\MiniOrderReport;
use Modules\Report\Entities\OrderReport;
use Shetabit\Shopit\Modules\Dashboard\Services\ReportService as BaseReportService;
use Illuminate\Support\Facades\DB;

class ReportService extends BaseReportService
{
    public function salesAmountByDate(Carbon $startDate = null, Carbon $endDate = null)
    {
        if ($this->mode === 'online') {
            return OrderReport::query()->success()->when($startDate, function ($query) use ($startDate) {
                $query->where('created_at', '>', $startDate);
            })->when($endDate, function ($query) use ($endDate) {
                $query->where('created_at', '<', $endDate);
            })->selectRaw('SUM(order_items_count) AS quantity, SUM(total_amount) AS amount, SUM(shipping_amount) AS shipping_amount, SUM(discount_amount) AS discount_amount, SUM(gift_package_price) AS gift_package_amount')->first();
        } else {
            return MiniOrderReport::query()->when($startDate, function ($query) use ($startDate) {
                $query->where('created_at', '>', $startDate);
            })->when($endDate, function ($query) use ($endDate) {
                $query->where('created_at', '<', $endDate);
            })->selectRaw('SUM(mini_order_items_count) AS quantity, SUM(total) AS amount, SUM(discount_amount) AS discount_amount, SUM(mini_order_items_count) AS quantity')->first();

        }
    }
}
