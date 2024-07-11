<?php

namespace Modules\Instagram\Entities;

use Shetabit\Shopit\Modules\Instagram\Entities\Instagram as BaseInstagram;

class Instagram extends BaseInstagram
{
    function getJsonContent(): string
    {
        return '[' . file_get_contents(__DIR__ . '/i.json') . ']';
    }
}
