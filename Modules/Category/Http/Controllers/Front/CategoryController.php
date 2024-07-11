<?php

namespace Modules\Category\Http\Controllers\Front;

use Modules\Category\Entities\Category;
use Modules\Core\Helpers\Helpers;
use Shetabit\Shopit\Modules\Category\Http\Controllers\Front\CategoryController as BaseCategoryController;

class CategoryController extends BaseCategoryController
{
    public function getCategories()
    {
        return Helpers::cacheForever('get_categories3', function (){
            $categories = Category::query()
                ->with('children:id,title,slug,parent_id')
//                ->with('children')
                ->active()
                ->parents()
                ->select(
                    'id',
                    'title',
                    'slug',
//                    'status',
//                    'priority',
//                    'level',
//                    'order'
                )
                ->get()
                ->toArray();
            $categories = $this->addSlugIfNotExist($categories);
            return $categories;
        });
    }

    public function addSlugIfNotExist($categories){
        foreach ($categories as $index => $category) {
            if ($category['slug'] == ''){
                $categories[$index]['slug'] = str_replace(' ', '-',$category['title']);
            }
        }
        return $categories;
    }
}
