<?php


namespace Modules\Core\Entities;


use Modules\Core\Services\QueryFiltersService;

trait HasFilters
{
    protected $queryFilterService;

    public function getQueryFilterService($query): QueryFiltersService
    {
        $prefix = null;
        if(method_exists($this, 'getTable')) {
            $prefix = $this->getTable();
        }

        return $this->queryFilterService ?? new QueryFiltersService($query, $prefix);
    }

    public function scopeDateFilter($query)
    {
        return $this->getQueryFilterService($query)->dateFilter();
    }

    public function scopeSortFilter($query)
    {
        return $this->getQueryFilterService($query)->sortFilter();
    }

    public function scopeSearchFilters($query)
    {
        return $this->getQueryFilterService($query)->searchFilters();
    }

    public function scopeFilters($query)
    {
        $this->scopeDateFilter($query);
        $this->scopeSortFilter($query);

        return $this->scopeSearchFilters($query);
    }
}
