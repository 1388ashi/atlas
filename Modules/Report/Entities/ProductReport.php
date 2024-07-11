<?php

namespace Modules\Report\Entities;

use Shetabit\Shopit\Modules\Report\Entities\ProductReport as BaseProductReport;
use Modules\Product\Entities\Variety;

class ProductReport extends BaseProductReport
{
    public function scopeDeletedVarietiesExcluded($query){
        return $query->whereNotIn('variety_id',Variety::query()->onlyTrashed()->pluck('id'));
    }
}
