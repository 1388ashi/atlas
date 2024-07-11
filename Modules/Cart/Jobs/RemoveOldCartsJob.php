<?php

namespace Modules\Cart\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Modules\Cart\Entities\Cart;

class RemoveOldCartsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public function __construct()
    {
        //
    }

    public function handle()
    {
        Cart::query()->where('created_at','<',now()->subHours(8))->delete();

        Log::debug('Removed carts older than 8 hours');
    }
}
