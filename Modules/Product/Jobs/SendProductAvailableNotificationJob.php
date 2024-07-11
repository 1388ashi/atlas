<?php

namespace Modules\Product\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Kutia\Larafirebase\Messages\FirebaseMessage;
use Modules\Customer\Entities\Customer;
use Modules\Product\Emails\ListenChargeMail;
use Modules\Product\Entities\ListenCharge;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Variety;
use Shetabit\Shopit\Modules\Core\Classes\CoreSettings;
use Shetabit\Shopit\Modules\Sms\Sms;

class SendProductAvailableNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $customerIds;
    public Variety $variety;

    private array|\Illuminate\Database\Eloquent\Collection $customerPhones;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($variety)
    {
        $customerIds = ListenCharge::query()->where('variety_id', $variety->id)
            ->get('customer_id')->pluck('customer_id')->toArray();

        $this->customerIds = $customerIds;
        $this->variety = $variety;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $tokens = DB::table('personal_access_tokens')
            ->where('tokenable_type', 'Modules\Customer\Entities\Customer')
            ->whereNotNull('device_token')
            ->whereIn('tokenable_id', $this->customerIds)
            ->get('device_token')->pluck('device_token')->toArray();

        $this->firebase($this->variety->product, $tokens);
        $this->sms($this->variety->product, $this->customerIds);
//        $this->mail($this->variety->product, $this->customerIds);
        $this->database($this->variety->product, $this->customerIds);

    }

    public function firebase($product, $tokens)
    {

        if (empty($tokens)) {
            return;
        }
        $mainImage = $product->main_image;
        $message =  (new FirebaseMessage())
            ->withTitle('موجودی جدید')
            ->withBody("محصول {$product->title} موجود شده است.")
            ->withClickAction('product/' . $product->id);
        if ($mainImage) {
            $message->withImage($mainImage->getFullUrl());
        }
        $message->asNotification(array_values(array_unique($tokens)));
    }

    public function database($product, $customerIds)
    {
        foreach ($customerIds as $id) {
            DatabaseNotification::query()->create([
                'id' => \Str::uuid(),
                'type' => 'SendProductAvailableNotification',
                'notifiable_type' => 'Modules\Customer\Entities\Customer',
                'notifiable_id' =>  $id,
                'data' => [
                    'product_id' => $product->id,
                    'description' => "مشتری عزیز {$product->title} شما موجود شد."
                ],
                'read_at' =>  null,
                'created_at' =>  now(),
                'updated_at' =>  now(),
            ]);
        }
    }

    public function mail($product, $customerIds)
    {

        $emails = Customer::query()->select(['id','email'])
            ->whereIn('id', $customerIds)
            ->whereNotNull('email')
            ->get('email')->pluck('email')->toArray();
        if (empty($emails)){
            return;
        }
        Mail::to($emails)->send(new ListenChargeMail($product));
    }

    public function sms($product, $customerIds)
    {
        $customerPhones= Customer::query()->whereIn('id',$customerIds )
            ->get('mobile')->pluck('mobile')->toArray();

        if (!app(CoreSettings::class)->get('sms.patterns.product-available', false)) {
            return;
        }
        $pattern = app(CoreSettings::class)->get('sms.patterns.product-available');

        foreach ($customerPhones as $customerPhone){
            Sms::pattern($pattern)->data([
                'product' => env('APP_URL_FRONT').'/product/'.$product->id,
            ])->to([$customerPhone])->send();
        }
    }

}
