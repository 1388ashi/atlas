<?php

namespace Modules\GiftPackage\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Modules\Area\Entities\City;
use Modules\Area\Entities\Province;
use Modules\Core\Classes\CoreSettings;
use Modules\Core\Entities\BaseModel;
use Modules\Core\Entities\HasCommonRelations;
use Modules\Core\Entities\HasFilters;
use Modules\Core\Helpers\Helpers;
use Modules\Core\Traits\HasAuthors;
use Modules\Core\Traits\InteractsWithMedia;
use Modules\Core\Transformers\MediaResource;
use Modules\Customer\Entities\Customer;
use Modules\Customer\Entities\CustomerRole;
use Modules\Order\Entities\Order;
use Modules\GiftPackage\Database\factories\GiftPackageFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\EloquentSortable\Sortable;
use Spatie\EloquentSortable\SortableTrait;
use Spatie\MediaLibrary\HasMedia;

class GiftPackage extends BaseModel implements Sortable, HasMedia
{
    use HasFactory, HasAuthors, HasCommonRelations, HasFilters,
        SortableTrait, InteractsWithMedia, LogsActivity;

    protected static $commonRelations = [
        
    ];
    public $sortable = [
        'order_column_name' => 'order',
        'sort_when_creating' => false,
    ];
    protected $fillable = [
        'name',
        'price',
        'order',
        'description',
        'status',
    ];
    protected $appends = ['image'];

    protected $hidden = ['media'];

    public static function booted()
    {
        static::deleting(function (GiftPackage $gift_package) {
            if ($gift_package->id == 1) {
                throw Helpers::makeValidationException('ادمین عزیز بسته بندی پیش فرض غیرقابل حذف میباشد.');
            }
        });
        static::deleting(function (GiftPackage $gift_package) {
            if ($gift_package->orders()->exists()) {
                throw Helpers::makeValidationException('به علت وجود سفارش برای این  بسته بندی هدیه, امکان حذف آن وجود ندارد');
            }
        });

    }



    protected static function newFactory()
    {
        return GiftPackageFactory::new();
    }

    public function getActivitylogOptions(): LogOptions
    {
        $admin = Auth::user();
        $name = !is_null($admin->name) ? $admin->name : $admin->username;
        return LogOptions::defaults()
            ->useLogName('GiftPackage')->logAll()->logOnlyDirty()
            ->setDescriptionForEvent(function ($eventName) use ($name) {
                $eventName = Helpers::setEventNameForLog($eventName);
                return "بسته بندی هدیه {$this->name} توسط ادمین {$name} {$eventName} شد";
            });
    }

    //Media library

    public function scopeActive($query)
    {
        $query->where('status', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function addImage($file)
    {
        $media = $this->addMedia($file)
            ->withCustomProperties(['type' => 'gift_package'])
            ->toMediaCollection('image');
        $this->load('media');

        return $media;
    }

    //Custom

    public function getImageAttribute(): ?MediaResource
    {
        $media = $this->getFirstMedia('image');
        if (!$media) {
            return null;
        }
        return new MediaResource($media);
    }




    //Relations

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

}
