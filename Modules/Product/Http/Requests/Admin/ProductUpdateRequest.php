<?php

namespace Modules\Product\Http\Requests\Admin;

use Illuminate\Validation\Rule;
use Modules\Core\Rules\Base64OrMediaId;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Variety;
use Shetabit\Shopit\Modules\Product\Entities\Gift;
use Shetabit\Shopit\Modules\Product\Http\Requests\Admin\ProductUpdateRequest as BaseProductUpdateRequest;

class ProductUpdateRequest extends BaseProductUpdateRequest
{
    public function rules()
    {

        return array_merge([
            // Product
            'product'                               => 'required|array',
            'product.title'                         => 'required|string|min:1|max:191',
            'product.short_description'             => 'nullable|string|min:4|max:191',
            'product.description'                   => 'nullable|string|min:10|max:10000',
            'product.unit_price'                    => 'nullable|integer|min:1000,'.Rule::requiredIf(fn () => empty($this->product['varieties'])),
            'product.purchase_price'                => 'nullable|integer|min:1',
            'product.discount_type'                 => ['nullable', 'string', Rule::in(Product::getAvailableDiscountTypes())],
            'product.discount_until'                => ['nullable','date_format:Y/m/d H:i'],
            'product.discount'                      => ['nullable','integer', 'required_with:product.discount_type'],
            'product.SKU'                           => 'nullable|string',
            'product.quantity'                      => 'nullable|required_without:product.varieties',
            'product.barcode'                       => 'nullable|string',
            'product.images'                        => ['nullable','array'],
            'product.images.*'                      => ['nullable', new Base64OrMediaId(Product::ACCEPTED_IMAGE_MIMES)],
            'product.brand_id'                      => 'nullable|exists:brands,id',
            'product.unit_id'                       => 'required|exists:units,id',
            'product.meta_description'              => 'nullable|string|max:15000',
            'product.meta_title'                    => 'nullable|string|max:191',
            'product.low_stock_quantity_warning'    => 'required|between:0,999999999999999.9999999999',
            'product.show_quantity'                 => 'required|boolean',
            'product.status'                        => ['required', Rule::in(Product::getAvailableStatuses())],
            'product.tags'                          => 'nullable|array',
            'product.published_at'                  => 'nullable',
            'product.categories'                    => 'required|array',
            'product.categories.*'                  => 'nullable|exists:categories,id',
            'product.gifts'                         => 'nullable|array',
            'product.gifts.*.id'                    => 'required_if:product.gifts.*,!=,null|integer|'.Rule::exists('gifts','id')
                    ->using(function ($query) { (new Gift())->scopeActive($query);}),
            'product.gifts.*.should_merge'          => 'required_if:product.gifts.*,!=,null|boolean',

            //size charts
            'product.size_charts'                   => 'nullable|array',
            'product.size_charts.*.title'           => 'required|string|min:1',
            'product.size_charts.*.chart'           => 'required|array',
            //specifications
            'product.specifications'                => 'nullable|array',
            'product.specifications.id'             => 'filled|exists:specifications,id',
            'product.specifications.value'          => 'nullable', // array , text
            'product.specifications.value.*'        => 'nullable|exists:specification_values', // values
            //varieties
            'product.varieties'                     => 'nullable|array',
            'product.varieties.*.price'             => 'required|integer',
            'product.varieties.*.SKU'               => 'nullable|string|min:2|max:191',
            'product.varieties.*.barcode'           => 'nullable|min:2',
            'product.varieties.*.purchase_price'    => 'nullable|integer|min:2',
            'product.varieties.*.discount_type'     => ['nullable', 'string', Rule::in((new Variety())->getAvailableDiscountTypes())],
            'product.varieties.*.discount'          => ['nullable','integer','required_with:product.varieties.*.discount_type'],
            'product.varieties.*.quantity'          => 'nullable|integer|min:0',
            'product.varieties.*.images'            => ['nullable','array'],
            'product.varieties.*.images.*'          => ['nullable', new Base64OrMediaId(Variety::ACCEPTED_IMAGE_MIMES)],
            // gift
            'product.varieties.*.gifts'             => 'nullable|array',
            'product.varieties.*.gifts.*.id'        => 'required_if:product.varieties.*.gifts.*,!=,null|integer|'.Rule::exists('gifts','id')
                    ->using(function ($query) { (new Gift())->scopeActive($query);}),
            // attribute
            'product.varieties.*.attributes'        => 'nullable',
            'product.varieties.*.attributes.*'      => 'nullable',
            'product.varieties.*.attributes.*.id'   => 'required|exists:attributes,id',
            'product.varieties.*.attributes.*.value'=> 'required', //id , text
            // Color
            'product.varieties.*.color_id'          => 'nullable|integer|exists:colors,id',

            // Is chargeable
            'product.chargeable' => 'nullable|boolean'
        ], $this->customRules());
    }
}
