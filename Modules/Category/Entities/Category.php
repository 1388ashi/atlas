<?php

namespace Modules\Category\Entities;

use Shetabit\Shopit\Modules\Category\Entities\Category as BaseCategory;

class Category extends BaseCategory
{
    public function children(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Category\Entities\Category::class , 'parent_id' , 'id')
//            ->orderBy('priority', 'DESC')
//            ->with([
//                'children:id,title,slug,parent_id',
////                'attributes.values',
////                'brands',
////                'specifications.values'
//            ])
            ->with(['children' => function($query) {
                $query->select('id','title','slug','parent_id');
            }]);

    }
}
