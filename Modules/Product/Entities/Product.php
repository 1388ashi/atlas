<?php

namespace Modules\Product\Entities;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Core\Entities\Media;
use Modules\Core\Helpers\Helpers;
use Modules\Core\Classes\DontAppend;
use Modules\Core\Transformers\MediaResource;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Modules\Customer\Entities\Customer;
use Shetabit\Shopit\Modules\Product\Entities\Product as BaseProduct;

class Product extends BaseProduct
{
    protected $appends = [
        'images', 'total_quantity', 'price', 'rate',
        'major_discount_amount', 'major_image', 'major_gifts', 'major_final_price', 'views_count', 'video_cover', 'video', 'is_vip'
    ];

    protected $fillable = [
        'title',
        'short_description',
        'description',
        'unit_price',
        'purchase_price',
        'discount_type',
        'discount_until',
        'discount',
        'SKU',
        'barcode',
        'brand_id',
        'unit_id',
        'meta_description',
        'meta_title',
        'low_stock_quantity_warning',
        'show_quantity',
        'chargeable',
        'status',
        'approved_at',
        'published_at',
        'threshold_quantity',
        'threshold_date',
        'slug',
    ];

    public function scopeActive($query, $operatorStatus = '!=', $status = self::STATUS_DRAFT)
    {
        //$status != init
        $query->whereNotIn('status', [self::STATUS_DRAFT,self::STATUS_INIT_QUANTITY])
            ->whereNotNull('approved_at');

        $customer = Auth::guard('customer-api')->user();
        if (!($customer instanceof Customer) || !($customer->canSeeUnpublishedProducts())) {
            $query->where('published_at', "<=", Carbon::now());
        }
    }

    const STATUS_INIT_QUANTITY = 'init_quantity'; // موجودی اولیه -> فقط ثبت میشه نه میشه تو فروشگاه حضوری فروخت نه میشه بیرون سایت فروخت

    public static function getAvailableStatuses(): array
    {
        return [static::STATUS_AVAILABLE,static::STATUS_OUT_OF_STOCK,
            static::STATUS_SOON, static::STATUS_DRAFT, static::STATUS_AVAILABLE_OFFLINE, static::STATUS_INIT_QUANTITY];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images');
        $this->addMediaCollection('video')->singleFile();
        $this->addMediaCollection('video_cover')->singleFile();
    }


    public function addVideoCover($image): void
    {
        if (empty($image)) return;
        Media::addMedia($image, $this, 'video_cover');
    }

    public function updateVideoCover($image): void
    {
        $updatedImage = Media::updateMedia($image, $this, 'video_cover');
        $mediaToDelete = $this->media()->where('collection_name', 'video_cover')->whereNotIn('id', $updatedImage)->get();
        foreach ($mediaToDelete as $media) {
            $media->delete();
        }
        $this->load('media');
    }

    public function getVideoCoverAttribute()
    {
        if (!$this->relationLoaded('media')) {
            return new DontAppend('Product getVideoCoverAttribute');
        }
        $media = $this->getMedia('video_cover')->first();

        return new MediaResource($media);
    }

    # video
    public function addVideo($video): void
    {
        // dd($video);
        if (empty($video)) dd('fjeoi');
        Media::addMedia($video, $this, 'video');
    }

    public function updateVideo($video): void
    {
        $updatedVideo = Media::updateMedia($video, $this, 'video');
        $mediaToDelete = $this->media()->where('collection_name', 'video')->whereNotIn('id', $updatedVideo)->get();
        foreach ($mediaToDelete as $media) {
            $media->delete();
        }
        $this->load('media');
    }

    public function getVideoAttribute()
    {
        if (!$this->relationLoaded('media')) {
            return new DontAppend('Product getVideoAttribute');
        }
        $media = $this->getMedia('video')->first();

        return new MediaResource($media);
    }

    # end Spatie function

    public function getIsVipAttribute()
    {
        return $this->published_at > now();
    }

    public function getSettedProductsAttribute()
    {
        // so fucking important!!
        $product = clone $this;
        return Helpers::removeVarieties($product->setAppends([])
            ->load([
                'sets.products' => fn ($q) => $q->withOnly(['media','varieties'=>fn($v)=>$v->withOnly([])])->select(['products.id', 'products.title','products.slug'])
            ])
            ->sets->map(fn ($set) => $set->products->each->setAppends(['major_image','video','video_cover']))
            ->flatten(1)
            ->filter(fn ($prod) => (($prod->id !== $product->id) && $prod->majorImage))->values()->toArray());
        //
    }

    public function imagesNew(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        $images = $this->hasMany(Media::class , 'model_id' , 'id')
            ->where('model_type','Modules\Product\Entities\Product')
            ->select(DB::raw("concat(uuid, '/', file_name) as url"),'model_id');
        return $images;
    }
}
