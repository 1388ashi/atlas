<?php

namespace Modules\ProductComment\Entities;

use Shetabit\Shopit\Modules\ProductComment\Entities\ProductComment as BaseProductComment;

class ProductComment extends BaseProductComment
{
    protected $fillable = [
        'title',
        'body',
        'rate',
        'show_customer_name', //وضعیت نمایش نام کاربر در کامنت گذاشته شده
        'status',
        'parent_id',
    ];

    public function childs()
    {
        return $this->hasMany(ProductComment::class,'parent_id')
            ->where('status',self::STATUS_APPROVED);
    }

}
