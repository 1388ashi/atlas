<?php

namespace Modules\Menu\Http\Controllers\All;

use Modules\Menu\Entities\MenuGroup;
use Modules\Menu\Entities\MenuItem;
use Shetabit\Shopit\Modules\Menu\Http\Controllers\All\MenuItemController as BaseMenuItemController;

class MenuItemController extends BaseMenuItemController
{
    public function menus($groupTitle)
    {
        if ($groupTitle == 'keys') {
            $keys = MenuGroup::pluck('title')->toArray();
            return response()->success('keys', ['keys' => $keys]);
        }
        $groupId = MenuGroup::whereTitle($groupTitle)->pluck('id')->first();
        $menus = MenuItem::query()
            ->orderBy('order', 'desc')
            ->where('group_id', $groupId)->isParent()
            ->active()->with('children', 'group')->get()->toArray();

        return response()->success($groupTitle . ' menus', ['menus' => $menus]);
    }
}
