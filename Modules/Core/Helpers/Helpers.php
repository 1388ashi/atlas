<?php

namespace Modules\Core\Helpers;

use Hekmatinasser\Verta\Verta;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Attribute\Entities\Attribute;
use Shetabit\Shopit\Modules\Core\Helpers\Helpers as BaseHelpers;

include_once "jdf.php";
include_once "convertDate.php";

class Helpers extends BaseHelpers
{
    public static function cacheRemember($key, $ttl, $callback)
    {
        return app()->environment('production') ? \Cache::remember($key, $ttl, $callback) : $callback();
    }

    public static function cacheForever($key, $callback)
    {
        return app()->environment('production') ? \Cache::rememberForever($key, $callback) : $callback();
    }

    public static function removeVarieties(array $products)
    {
        return parent::removeVarieties($products);
    }

    public static function getImages($model, $id, $getAllSizes = false)
    {
        $model_type = match ($model) {
            'Advertise' => 'Modules\Advertise\Entities\Advertise',
            'Post' => 'Modules\Blog\Entities\Post',
            'Category' => 'Modules\Category\Entities\Category',
            'Customer' => 'Modules\Customer\Entities\Customer',
            'Flash' => 'Modules\Flash\Entities\Flash',
            'GiftPackage' => 'Modules\GiftPackage\Entities\GiftPackage',
            'Instagram' => 'Modules\Instagram\Entities\Instagram',
            'Product' => 'Modules\Product\Entities\Product',
            'Variety' => 'Modules\Product\Entities\Variety',
            'Shipping' => 'Modules\Shipping\Entities\Shipping',
            'Slider' => 'Modules\Slider\Entities\Slider',
            default => '',
        };

        $images = DB::table('media')
            ->where('model_type',$model_type)
            ->where('model_id',$id)
//            ->select(DB::raw("concat(uuid, '/', file_name) as url"))
//            ->select(DB::raw("concat(uuid,'/',file_name) as url"))
            ->select(
                'uuid',
                'file_name'
            )
            ->get();

        if (count($images)>0){
            // در صورتی که مدل درخواست شده محصول بود و دارای تصویر هم بود تصاویر خود محصول برگشت داده میشود.
            $list_images = [];
            if ($getAllSizes){
                foreach ($images as $image) {
                    $image_file_name_array = explode('.',$image->file_name);
                    $file_name = $image_file_name_array[0];
                    $file_extension = $image_file_name_array[1];
                    $list_images[] = [
                        'lg' => "$image->uuid/conversions/$file_name-thumb_lg.$file_extension",
                        'md' => "$image->uuid/conversions/$file_name-thumb_md.$file_extension",
                        'sm' => "$image->uuid/conversions/$file_name-thumb_sm.$file_extension"
                    ];
                }
            } else {
                foreach ($images as $image) {
                    $list_images[] = "$image->uuid/$image->file_name";
                }
            }
            return $list_images;
        } elseif ($model == 'Product' && count($images)==0){
            // در صورتی که مدل درخواست شده Product بود ولی تصویری برای آن یافت نشد تصاویر تنوع آن دریافت شده و برگشت داده می شود
            // اولویت تصویر با موردی است که به عنوان تنوع پیش فرض درنظر گرفته شده است
            $varieties = DB::table('varieties')->select('id')->where('product_id',$id)->orderBy('is_head','desc')->get();

            $variety_images = [];
            foreach ($varieties as $v) {
                if (self::getImages('Variety',$v->id)){
                    $variety_images[] = self::getImages('Variety',$v->id)[0];
                }
            }
            return $variety_images;
        }
    }

    public static function getProductRate($id)
    {
        return round(DB::table('product_comments')->where('status','approved')->where('product_id',$id)->avg('rate'),1);
    }

    public static function getProductViewsCount($id)
    {
        return DB::table('views')->where('viewable_type','Modules\Product\Entities\Product')->where('viewable_id',$id)->count();
    }

    public static function getProductVarieties($id)
    {
        $varieties = DB::table('varieties as v')
            ->join('stores as s','s.variety_id','=','v.id')
            ->where('v.product_id',$id)
            ->select(
                "v.id",
                "v.price",
//                    "SKU",
//                    "barcode",
//                    "purchase_price",
                "v.product_id",
                "v.color_id",
                "v.discount_type",
                "v.discount",
//                    "created_at",
//                    "updated_at",
//                    "order",
//                    "deleted_at",
//                    "max_number_purchases",
                "v.discount_until",
                "v.name",
//                    "description",
                "s.balance as quantity",
            )
            ->get();
        foreach ($varieties as $variety) {
//            $variety->unique_attributes_key = 0;
            $variety->images = self::getImages('Variety',$variety->id);
            $variety->quantity = DB::table('stores')->where('variety_id',$variety->id)->pluck('balance')->first();
            $variety->attributes = self::getVarietyAtributes($variety);


            $has_discount = ($variety->quantity != 0 && $variety->discount_until && ($variety->discount_until > now()));

            $discount_price = $has_discount ? $variety->discount_type=='percentage' ? $variety->price*$variety->discount/100 : $variety->discount : 0;
            $discount_value = $has_discount ? $variety->discount : 0;

            if ($has_discount) {
                $discount_type = $variety->discount_type;
            } else {
                $discount_type = null;
            }

            $amount = $variety->price - $discount_price;

            $variety->final_price = [
                "discount_type"=> $discount_type,
                "discount"=> $discount_value,
                "discount_price"=> $discount_price,
                "amount"=> $amount
            ];

        }

        return $varieties;
    }

    public static function getVarietyAtributes($variety)
    {
        //        dd($attributes);
//        foreach ($attributes as $attribute) {
//            dd('c' . $variety->color_id . 'a' . $attribute->id . $attribute->value . $attribute->attribute_value_id);
//        }
        $attributes = DB::table('attribute_variety as av')
            ->join('attributes as a','a.id','=','av.attribute_id')
//            ->leftJoin('attribute_values as pivot','pivot.id','=','av.attribute_value_id')
            ->select(
                "a.id",
                "av.attribute_value_id",
//                "av.variety_id",
                "a.name",
                "a.label",
                "a.type",
//                "a.show_filter",
                "a.style",
//                "a.public",
                "a.status",
//                "pivot.value",
//                "av.attribute_value_id"
            )
            ->where('av.variety_id',$variety->id)
            ->get();

        foreach ($attributes as $attribute) {
            $attribute->pivot = self::getAtributePivot($attribute);
        }

        return $attributes;
    }

    public static function getAtributePivot($attribute)
    {
        $pivot = DB::table('attribute_values')
            ->select(
                "id",
                "attribute_id",
                "value",
                "selected",
            )
            ->where('id',$attribute->attribute_value_id)
            ->first();

        return $pivot;
    }

    public static function getMajorFinalPrice($id)
    {
        $varieties = DB::table('varieties as v')
            ->join('stores as s','s.variety_id','=','v.id')
            ->where('v.product_id',$id)
            ->select(
                "v.discount_type",
                "v.discount",
                "v.price",
//                "product_id",
                "v.discount_until",
                "s.balance as quantity",
            )
            ->get();

        $product = DB::table('products')->where('id',$id)->first();

        $product_has_discount = ($product->discount_until && ($product->discount_until > now()));
        $product_discount_price = $product_has_discount ? $product->discount_type=='percentage' ? $product->unit_price*$product->discount/100 : $product->discount : 0;
        $product_discount_value = $product_has_discount ? $product->discount : 0;

        if (!$varieties){
            return (object)[
//                "discount_model"=> "none",
                "discount_type"=> $product->discount_type,
                "discount"=> $product_discount_value,
                "discount_price"=> $product_discount_price,
                "amount"=> $product->unit_price - $product_discount_price
            ];
        }

        $final_amount = PHP_INT_MAX;
        $final_discount = 0;
        $final_discount_price = 0;
        $final_discount_type = null;

        foreach ($varieties as $variety) {

            $has_variety_discount = ($variety->quantity != 0 && $variety->discount_until && ($variety->discount_until > now()));

            $discount_price = $has_variety_discount ? $variety->discount_type=='percentage' ? $variety->price*$variety->discount/100 : $variety->discount : 0;
            $discount_value = $has_variety_discount ? $variety->discount : 0;

            if ($has_variety_discount) {
                $discount_type = $variety->discount_type;
            } else {
                $discount_type = null;
            }

            $amount = $variety->price - $discount_price;

//            dump(($has_variety_discount?"yes ":"no  ") . $discount_price . " | " . $discount_value . " | " . $amount . " | " . $discount_type);
//            dump($amount . " | " . $final_amount);
            if ($amount < $final_amount){
                $final_amount = $amount;
                $final_discount = $discount_value;
                $final_discount_price = $discount_price;
                $final_discount_type = $discount_type;
            }
//            dd(($has_variety_discount?"yes ":"no  ") . $final_discount_price . " | " . $final_discount . " | " . $final_amount . " | " . $final_discount_type);
//            dump(($has_variety_discount?"yes ":"no  ") . $final_discount_price . " | " . $final_discount . " | " . $final_amount . " | " . $final_discount_type);
        }

//        dd('done');
//        dd($product_discount_price , $final_discount_price , $has_variety_discount);
        if ($product_discount_price < $final_discount_price || !$has_variety_discount){
            $final_amount = $product->unit_price;
            $final_discount = $product_discount_value;
            $final_discount_price = $product_discount_price;
            $final_discount_type = $product->discount_type;
        }

        return (object)[
//                "discount_model"=> "none",
            "discount_type"=> $final_discount_type,
            "discount"=> $final_discount,
            "discount_price"=> $final_discount_price,
            "amount"=> $final_amount
        ];
    }

    public static function getRelatedProducts($product){
        $product_categories = DB::table('category_product')
            ->select('category_id')
            ->where('product_id',$product->id)
            ->get()
            ->pluck('category_id');

        $relatedProductIds = DB::table('category_product')
            ->join('products','category_product.product_id','=','products.id')
            ->whereNotIn('product_id', [$product->id])
            ->whereIn('category_id',$product_categories)
            ->whereIn('products.status',['available','out_of_stock'])
            ->inRandomOrder()
            ->select('product_id')
            ->limit(6)
            ->get()
            ->pluck('product_id');

        $relatedProducts = DB::table('products')
            ->whereIn('id',$relatedProductIds)
            ->select(
                'id',
                'title',
                'slug',
                'status',
            )
            ->get();

        foreach ($relatedProducts as $relatedProduct) {
            $relatedProduct->images = self::getImages("Product", $relatedProduct->id);
            $relatedProduct->rate = Helpers::getProductRate($relatedProduct->id);
            $relatedProduct->major_final_price = Helpers::getMajorFinalPrice($relatedProduct->id);
            $relatedProduct->major_discount_amount = $relatedProduct->major_final_price->discount;
            $relatedProduct->major_discount_type = $relatedProduct->major_final_price->discount_type;
        }

        return $relatedProducts;
    }


    function getStatusesForReport(): array
    {
        return ['new','delivered', 'in_progress','reserved'];
    }
    public function updateOrdersUsefulData(){

        DB::table('orders')
            ->whereNull('province')
            ->whereNull('reserved_id')
            ->update([
//                'receiver' => DB::raw("CONCAT(CAST(json_unquote(JSON_EXTRACT(address, '$.first_name')) as CHAR), ' ', CAST(json_unquote(JSON_EXTRACT(address, '$.last_name')) as CHAR))"),
                'first_name' => DB::raw("CAST(json_unquote(JSON_EXTRACT(address, '$.first_name')) as CHAR)"),
                'last_name' => DB::raw("CAST(json_unquote(JSON_EXTRACT(address, '$.last_name')) as CHAR)"),
                'city' => DB::raw("trim(CAST(json_unquote(JSON_EXTRACT(address, '$.city.name')) as CHAR))"),
                'province' => DB::raw("trim(CAST(json_unquote(JSON_EXTRACT(address, '$.city.province.name')) as CHAR))"),
            ]);
    }

    public function updateOrdersCalculateData($order_id=null){
        if ($order_id){
            $orders = DB::table('orders')
                ->where('id',$order_id)
                ->get();
        } else {
            $orders = DB::table('orders')
                ->whereNull('items_count')
                ->whereNull('reserved_id')
                ->when(env('APP_ENV') === 'local', function ($query) {
                    $query->take(100);
                })
                ->get();
        }

        foreach ($orders as $order) {
            $this->calculateOrderFields($order);
        }
    }

    public function calculateOrderFields($order, $return_total_amount = false)
    {
        // محاسبه ارقام مربوط به آیتم های سفارش
        $count = DB::table('order_items')->where('order_id',$order->id)->count();
        $quantity = DB::table('order_items')->select(DB::raw('sum(quantity) as q'))->where('order_id',$order->id)->where('status',1)->first()->q;
        $amount = DB::table('order_items')->select(DB::raw('sum(quantity*amount) as s'))->where('order_id',$order->id)->where('status',1)->first()->s;
        $discount = DB::table('order_items')->select(DB::raw('sum(quantity*discount_amount) as d'))->where('order_id',$order->id)->where('status',1)->first()->d;
        $shipping = DB::table('orders')->select('shipping_amount')->where('id',$order->id)->first()->shipping_amount;

        // Log::info($order->id . ': original quantity = ' . $quantity);

        $used_wallet_amount = DB::table('invoices')
            ->select('wallet_amount')
            ->where('payable_id',$order->id)
            ->where('payable_type',"Modules\Order\Entities\Order")
            ->where('status','success')
            ->first()->wallet_amount??0;

        // در صورتی که روی آیتم ها تخفیف داده شده باشد، تخفیف مورد نظر روی amount نیز اعمال شده است
        // پس هنگام محاسبه amount مقدار تخفیف را با آن جمع بسته تا مقدار واقعی آن محاسبه گردد
        $amount += $discount;

        // Log::info($order->id . ': final discount amount = ' . $discount);
        // Log::info($order->id . ': calculated amount = ' . $amount);

        if ($order->coupon_id){
            $coupon = DB::table('coupons')->find($order->coupon_id);
            $undiscounted_amount = DB::table('order_items')
                ->select(DB::raw('sum(quantity*amount) as s'))
                ->where('order_id',$order->id)
                ->where('status',1)
                ->where('discount_amount',0)
                ->first()->s;
            if ($coupon->type == 'flat'){
                $discount += $coupon->amount;
            } else {
                $discount += $coupon->amount * $undiscounted_amount / 100;
            }
        }

        // Log::info($order->id . ': final discount amount = ' . $discount . " ($undiscounted_amount)");

        // افزودن مقادیرمربوط به سفارشات زیر مجموعه سفارش اصلی
        $sub_orders = DB::table('orders')->where('reserved_id',$order->id)->whereIn('status',['new','delivered','in_progress','reserved'])->get();

//            Log::info('sub_orders for reCalculate:');
//            foreach ($sub_orders as $sub_order) {
//                Log::info('sub_order_id = ' . $sub_order->id);
//            }

        foreach ($sub_orders as $sub_order) {
            $count += DB::table('order_items')->where('order_id',$sub_order->id)->count();
            $quantity += DB::table('order_items')->select(DB::raw('sum(quantity) as q'))->where('order_id',$sub_order->id)->where('status',1)->first()->q;
            $amount += DB::table('order_items')->select(DB::raw('sum(quantity*amount) as s'))->where('order_id',$sub_order->id)->where('status',1)->first()->s;
            $child_discount = DB::table('order_items')->select(DB::raw('sum(quantity*discount_amount) as d'))->where('order_id',$sub_order->id)->where('status',1)->first()->d;

//                Log::info('new quantity = ' . $quantity);

            $child_amount = DB::table('order_items')->select(DB::raw('sum(quantity*amount) as s'))->where('order_id',$sub_order->id)->where('status',1)->first()->s;

            // در صورتی که روی آیتم های زیر مجموعه تخفیف داده شده باشد، تخفیف مورد نظر روی amount نیز اعمال شده است
            // پس هنگام محاسبه amount مقدار تخفیف را با آن جمع بسته تا مقدار واقعی آن محاسبه گردد
            $amount += $child_discount;

            if ($sub_order->coupon_id){
                $coupon = DB::table('coupons')->find($sub_order->coupon_id);
                if ($coupon->type == 'flat'){
                    $child_discount += $coupon->amount;
                } else {
                    $child_discount += $coupon->amount * $child_amount / 100;
                }
            }

            // Log::info($order->id . ': final discount amount on child = ' . $child_discount . " ($child_amount)");

            $discount += $child_discount;
        }

        DB::table('orders')
            ->where('id',$order->id)
            ->update([
                'items_count' => $count,
                'items_quantity' => $quantity,
                'total_amount' => $amount,
                'discount_amount' => $discount,
                'used_wallet_amount' => $used_wallet_amount,
                'total_payable_amount' => $amount + $shipping - $discount - $used_wallet_amount,
            ]);

        if ($return_total_amount){
            return $amount;
        }
    }

    public function updateChargeTypeOfTransactions(){

        $order_gift_id = DB::table('charge_types')->where('value','order_gift')->value('id');
        $instagram_gift_id = DB::table('charge_types')->where('value','instagram_gift')->value('id');
        $customers_club_gift_id = DB::table('charge_types')->where('value','customers_club_gift')->value('id');
        $cancel_order_id = DB::table('charge_types')->where('value','cancel_order')->value('id');
        $online_charge_id = DB::table('charge_types')->where('value','online_charge')->value('id');
        $other_id = DB::table('charge_types')->where('value','other')->value('id');

        // به روز رسانی شناسه نوع واریز تراکنش های هدیه سفارش
        DB::table('transactions')
            ->whereNotNull('meta')
            ->whereRaw("CAST(json_unquote(JSON_EXTRACT(meta, '$.description')) as CHAR) like '%هدیه خرید سفارش%'")
            ->whereNull('charge_type_id')
            ->update([
                'charge_type_id' => $order_gift_id
            ]);

        // به روز رسانی شناسه نوع واریز تراکنش های مسابقات اینستاگرامی
        DB::table('transactions')
            ->whereNotNull('meta')
            ->whereRaw("CAST(json_unquote(JSON_EXTRACT(meta, '$.description')) as CHAR) like '%اینستاگرام%'")
            ->whereNull('charge_type_id')
            ->update([
                'charge_type_id' => $instagram_gift_id
            ]);

        // به روز رسانی شناسه نوع واریز تراکنش های لغو شده
        DB::table('transactions')
            ->whereNotNull('meta')
            ->whereRaw("CAST(json_unquote(JSON_EXTRACT(meta, '$.description')) as CHAR) like '%با وضعیت لغو شده%'")
            ->whereNull('charge_type_id')
            ->update([
                'charge_type_id' => $cancel_order_id
            ]);

        // به روز رسانی شناسه نوع واریز تراکنش برگشت مبلغ
        DB::table('transactions')
            ->whereNotNull('meta')
            ->whereRaw("CAST(json_unquote(JSON_EXTRACT(meta, '$.description')) as CHAR) like '%برگشت مبلغ سفارش در اثر تغییر وضعیت به%'")
            ->whereNull('charge_type_id')
            ->update([
                'charge_type_id' => $cancel_order_id
            ]);

        // به روز رسانی شناسه نوع واریز تراکنش شارژ آنلاین
        DB::table('transactions')
            ->whereNull('meta')
            ->whereNull('charge_type_id')
            ->update([
                'charge_type_id' => $online_charge_id
            ]);

        // به روز رسانی شناسه نوع واریز تراکنش های متفرقه
        DB::table('transactions')
            ->where('type','deposit')
            ->whereNull('charge_type_id')
            ->update([
                'charge_type_id' => $other_id
            ]);
    }

    public function updateMiniOrdersCalculateData($mini_order_id=null){
        if ($mini_order_id){
            $mini_orders = DB::table('mini_orders')
                ->where('id',$mini_order_id)
                ->get();
        } else {
            $mini_orders = DB::table('mini_orders')
                ->whereNull('items_count')
                ->when(env('APP_ENV') === 'local', function ($query) {
                    $query->take(1000);
                })
                ->get();
        }

        foreach ($mini_orders as $mini_order) {
            $this->calculateMiniOrderFields($mini_order);
        }
    }

    public function calculateMiniOrderFields($mini_order, $return_total_amount = false)
    {
        // محاسبه ارقام مربوط به آیتم های سفارش
        $count = DB::table('mini_order_items')->where('mini_order_id',$mini_order->id)->where('type','sell')->count()??0;
        $quantity = DB::table('mini_order_items')->select(DB::raw('sum(quantity) as q'))->where('mini_order_id',$mini_order->id)->where('type','sell')->first()->q??0;
        $amount = DB::table('mini_order_items')->select(DB::raw('sum(quantity*amount) as s'))->where('mini_order_id',$mini_order->id)->where('type','sell')->first()->s??0;
        $discount = DB::table('mini_order_items')->select(DB::raw('sum(quantity*discount_amount) as d'))->where('mini_order_id',$mini_order->id)->where('type','sell')->first()->d??0;
        $count_refund = DB::table('mini_order_items')->where('mini_order_id',$mini_order->id)->where('type','refund')->count()??0;
        $quantity_refund = DB::table('mini_order_items')->select(DB::raw('sum(quantity) as q'))->where('mini_order_id',$mini_order->id)->where('type','refund')->first()->q??0;
        $amount_refund = DB::table('mini_order_items')->select(DB::raw('sum(quantity*amount) as s'))->where('mini_order_id',$mini_order->id)->where('type','refund')->first()->s??0;
        $discount_refund = DB::table('mini_order_items')->select(DB::raw('sum(quantity*discount_amount) as d'))->where('mini_order_id',$mini_order->id)->where('type','refund')->first()->d??0;

//        Log::info("calc: \n mini_order_id : $mini_order->id \t\t amount: $amount \t\t amount_refund: $amount_refund");

        $amount -= $amount_refund;
        $discount -= $discount_refund;

//        Log::info("final amount : $amount");

        // در صورتی که روی آیتم ها تخفیف داده شده باشد، تخفیف مورد نظر روی amount نیز اعمال شده است
        // پس هنگام محاسبه amount مقدار تخفیف را با آن جمع بسته تا مقدار واقعی آن محاسبه گردد
        $amount += $discount;

        DB::table('mini_orders')
            ->where('id',$mini_order->id)
            ->update([
                'items_count' => $count,
                'items_quantity' => $quantity,
                'items_count_refund' => $count_refund,
                'items_quantity_refund' => $quantity_refund,
                'total_amount' => $amount,
                'total_amount_sell' => $amount+$amount_refund,
                'total_amount_refund' => $amount_refund,
                'discount_amount' => $discount,
            ]);

        if ($return_total_amount){
            return $amount;
        }
    }

    public function getToday(){
        return date('Y-m-d');
    }

    public function getThisYearPersian(){
        return $this->convertMiladiToShamsi($this->getToday(),"Y");
    }

    public function firstDayOfWeek(){
        date_default_timezone_set('UTC');   // Set the timezone to the desired one
        $currentDate = date('Y-m-d');   // Get the current date
        $startDayOfWeek = 'Saturday';   // Set the start day of the week (Saturday)
        return date('Y-m-d', strtotime("last $startDayOfWeek", strtotime($currentDate)));   // Find the most recent occurrence of the start day of the week
    }

    public function convertMiladiToShamsi($date,$format="Y/m/d"){
        $verta = new Verta($date);
        return $verta->format($format);
    }

    public function convertShamsiToMiladi($date){
        return convertShamsiToMiladiWithoutTime($date);
    }

    public function getDaysOfMonth($year, $month){
        switch ($month){
            case 1:
            case 2:
            case 3:
            case 4:
            case 5:
            case 6:
                $days = 31;
                break;

            case 7:
            case 8:
            case 9:
            case 10:
            case 11:
                $days = 30;
                break;

            case 12:
                $days = $this->isKabise($year)?30:29;
                break;
        }

        return $days;
    }

    public function getDaysOfYear($year){
        return $this->isKabise($year)?366:365;
    }

    public function isKabise($year): bool
    {
        $list = [1, 5, 9, 13, 17, 22, 26, 30];
        $a = $year % 33;
        if (in_array($a,$list))
            return true;
        else
            return false;
    }
}
