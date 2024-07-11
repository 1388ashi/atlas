<?php

namespace Modules\Link\Http\Controllers\Admin;

use Modules\Core\Classes\CoreSettings;
use Shetabit\Shopit\Modules\Link\Http\Controllers\Admin\LinkController as BaseLinkController;

class LinkController extends BaseLinkController
{
    public function index()
    {
        $links = app(CoreSettings::class)->get('linkables');

        return response()->success('', ['linkables' => $links]);
    }
}
