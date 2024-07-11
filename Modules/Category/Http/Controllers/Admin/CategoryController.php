<?php

namespace Modules\Category\Http\Controllers\Admin;

use Modules\Category\Entities\Category;
use Shetabit\Shopit\Modules\Category\Http\Controllers\Admin\CategoryController as BaseCategoryController;

class CategoryController extends BaseCategoryController
{
    public function index()
    {
        $categories = Category::query()
            ->parents()
            ->orderBy('priority', 'DESC')
            ->filters();
        if (\request('all')) {
            $categories->with('children');
        }
        $categories = $categories->paginateOrAll();

        return response()->success('تمام دسته بندی ها', compact('categories'));

    }
}
