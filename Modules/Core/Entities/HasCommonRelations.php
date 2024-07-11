<?php


namespace Modules\Core\Entities;


use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasCommonRelations
 * @package Modules\Core\Entities
 * @method   Builder withCommonRelations()
 * @property $commonRelations; // should be static
 */
trait HasCommonRelations
{

    public function scopeWithCommonRelations($query)
    {
        if (isset(static::$commonRelations) && !empty(static::$commonRelations)) {
            $query->with(static::$commonRelations);
        }
    }

    public function loadCommonRelations()
    {
        if (isset(static::$commonRelations) && !empty(static::$commonRelations)) {
            $this->load(static::$commonRelations);
        }

        return $this;
    }
}
