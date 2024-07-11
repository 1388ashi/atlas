<?php

namespace Modules\Flash\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Modules\Flash\Entities\Flash;
use Shetabit\Shopit\Modules\Flash\Http\Controllers\Admin\FlashController as BaseFlashController;

class FlashController extends BaseFlashController
{
    public function index()
    {
        $flashes = Flash::query()
            ->latest('id')
            ->filters()
            ->when(request('active'), function (Builder $query) {
                $now = Carbon::now();
                return $query->whereDate('start_date', '>=', $now)
                    ->whereDate('end_date', '<=', $now)
                    ->where('status', '=', 1);
            })
            ->paginateOrAll();

        return response()->success('Get all flashes', compact('flashes'));
    }
}
