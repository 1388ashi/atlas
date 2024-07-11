<?php

namespace Modules\Widget\Classes;

use Shetabit\Shopit\Modules\Widget\Classes\Widget as BaseWidget;

class Widget extends BaseWidget
{
    public static function applyWidgets(&$data)
    {
        $request = request();
        if ($request->input('is_widgeting')) {
            return;
        }
        if (!$request->filled('widgets')) {
            return;
        }
        $request->merge(['is_widgeting' => true, 'all' => 1]);
        $data['widgets'] = [];
        try {
            $widgets = json_decode($request->query('widgets'));
        } catch (\Exception $exception) {
            return;
        }

        // Remove filters
        for ($i = 1; $i < 99; $i++) {
            $request->merge([
                'search' . $i => null
            ]);
        }

        $user = \Auth::user() ? \Auth::user()::class : 'all';
        $configuredWidgets = config('widget.' . $user);
        /**
         * @var $widgets از درخواست
         */
        foreach ($widgets as $widget) {
            $params = [];
            if (str_contains($widget, ':')) {
                $temp = explode(':', $widget);
                $widget = $temp[0];
                $params = array_slice($temp, 1);
            }
            if (array_key_exists($widget, $configuredWidgets)) {
                $widgetData = static::getWidgetData($configuredWidgets[$widget][0], $configuredWidgets[$widget][1], $params);
                foreach ($widgetData as $key => $datum) {
                    $temp = $data['widgets'];
                    $temp[$key] = $datum;
                    $data['widgets'] = $temp;
                }
            }
        }

    }
}
