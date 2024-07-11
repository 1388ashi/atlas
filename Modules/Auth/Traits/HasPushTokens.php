<?php


namespace Modules\Auth\Traits;

use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\Sanctum;

trait HasPushTokens
{
    public function getPushTokens()
    {
        $user = $this;
        /**
         * @var $user HasApiTokens
         */
        $deviceTokens = $user->tokens()->whereNotNull('device_token')
            ->get(['device_token'])->pluck('device_token')->toArray();

        return $deviceTokens;
    }

    public function getUserTypeForPushToken()
    {
        $user = $this;
        if ($user instanceof User) {
            return 'user';
        }
        if ($user instanceof Business) {
            return 'business';
        }

        return 'unkown';
    }
}
