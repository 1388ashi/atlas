<?php

namespace Modules\Core\Entities;

use Shetabit\Shopit\Modules\Core\Entities\BaseEloquentBuilder as BaseBaseEloquentBuilder;
use http\Exception\BadMethodCallException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\Relation;
use Modules\Core\Scopes\StatusScope;
use Modules\Product\Entities\Product;

class BaseEloquentBuilder extends BaseBaseEloquentBuilder
{
    public function latest($column = null)
    {
        if ($this->model instanceof Product) {
           // $this->query->orderBy('published_at', 'desc')->orderBy('id', 'desc');
          $this->query->orderBy('created_at', 'desc');
        }
        if (is_null($column)) {
            $column = $this->model->getCreatedAtColumn() ?? 'created_at';
        }

        $this->query->latest($column);

        return $this;
    }
}
