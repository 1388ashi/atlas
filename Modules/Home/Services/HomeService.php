<?php

namespace Modules\Home\Services;

use Modules\Core\Entities\Media;
use Shetabit\Shopit\Modules\Home\Services\HomeService as BaseHomeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use JetBrains\PhpStorm\ArrayShape;
use Modules\Advertise\Entities\Advertise;
use Modules\Attribute\Entities\Attribute;
use Modules\Blog\Entities\Post;
use Modules\Brand\Entities\Brand;
use Modules\Cart\Classes\CartFromRequest;
use Modules\Category\Entities\Category;
use Modules\Color\Entities\Color;
use Modules\Core\Classes\CoreSettings;
use Modules\Core\Helpers\Helpers;
use Modules\Core\Services\NotificationService;
use Modules\Customer\Entities\Customer;
use Modules\FAQ\Entities\FAQ;
use Modules\Flash\Entities\Flash;
use Modules\Instagram\Entities\Instagram;
use Modules\Invoice\Entities\Payment;
use Modules\Menu\Entities\MenuItem;
use Modules\Order\Entities\OrderItem;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Recommendation;
use Modules\Product\Entities\Variety;
use Modules\Setting\Entities\Setting;
use Modules\Slider\Entities\Slider;
use SebastianBergmann\LinesOfCode\LinesOfCode;
use Modules\Core\Entities\BaseEloquentBuilder;
use Modules\Product\Services\ProductService;

class HomeService extends BaseHomeService
{
    public function defaults()
    {
        return [
            'gateways' => $this->getGateways()
        ];
    }
//    public function getByRecommendations($take, $group): Collection|\Illuminate\Support\Collection
//    {
//        $recommendationIds = Recommendation::query()
//            ->byGroup($group)
//            ->take($take)
//            ->latest('id')
//            ->get(['product_id'])
//            ->pluck('product_id')
//            ->toArray();
//
//        if (empty($recommendationIds)) {
//            return collect();
//        }
//
//        $products=  Product::query()
//            ->latest('id')
//            ->with([/*'categories', 'activeFlash',*/ 'varieties'])
//            ->select(
//                'title',
//                'id',
//                'slug',
//                'status',
////                'rate',
////                'major_final_price',
////                'major_image',
////                'images',
//            )
//            ->whereIn('id', $recommendationIds)->get();
//        foreach ($products as $product){
//            $product->makeHidden('SKU');
//            $product->makeHidden('views_count');
//            $product->makeHidden('major_discount_amount');
//            $product->makeHidden('price');
//            $product->makeHidden('video_cover');
//            $product->makeHidden('video');
//            $product->makeHidden('is_vip');
//        }
//
//        return $products;
//
//    }

    public function functions(): array
    {
        return [
            'user'=> 'getUser',
            'menu'=> 'getMenu',
            'post'=> 'getPost',
            'flashes'=> 'getFlashes',
            'settings'=> 'getSettings',
            'advertise'=> 'getAdvertise',
            'instagram'=> 'getInstagramPosts',
            'categories'=> 'getCategory',
            'size_values'=> 'getSizeValueProduct',
            'special_categories'=> 'getSpecialCategory',
            'cart_request' => 'getCartFromRequest',
            'colors' => 'getColors',
            'f_a_qs' => 'getFAQs',
            'brands' => 'getBrands',
        ];
    }


    public function mostDiscount(){
        return [
            'mostDiscount'=> 'getMostDiscount',
        ];
    }

    public function mostSales(){
        return [
            'mostSales'=> 'getMostSales',
        ];
    }


    public function newProducts(){
        return [
            'new_products'=> 'getNewProduct',
        ];
    }



    public function suggestions(){
        return [
            'suggestions'=> 'getSuggestions',
        ];
    }



    public function sliders(){
        return [
            'sliders'=> 'getSlider',
        ];
    }

    public function advertize(){
        return [
            'advertise'=> 'getAdvertise',
        ];
    }

    public function specialCategory(){
        return [
            'special_categories'=> 'getSpecialCategory',
        ];
    }

    public function discountProduct(){
        return [
            'discount_products' => 'getDiscountProducts',
        ];
    }


    public function VipUnpublishedProducts(){
        return [
            'vip_unpublished_products' => 'getVipUnpublishedProducts',
        ];
    }

    public function getSuggestions($take = 10)
    {
        $recommendation = $this->getByRecommendations($take,'suggestions');

        if (count($recommendation) == 0){
            $catIds = DB::table('model_views')->orderBy('count', 'DESC')->where('ip', Request::ip())
                ->take(5)->get('model_id')->pluck('model_id')->toArray();
            return Helpers::removeVarieties(Product::query()
                ->with(['categories', 'activeFlash', 'varieties'])
                ->available(true)
                ->whereHas('categories' ,function ($query) use ($catIds){
                    $query->whereIn('id', $catIds);
                })->latest()->take($take)->get()->toArray());
        }
        return Helpers::removeVarieties($recommendation->toArray());
    }

    //محصولاتی که زدگی دارن رو میاد ارزونتر میده
    public function getDiscountProducts($take = 1000)
    {
        $recommendation = $this->getByRecommendations($take,'discount');

        return (count($recommendation) != 0)
            ? Helpers::removeVarieties($recommendation->toArray())
            : Helpers::removeVarieties(Product::query()->orderBy('updated_at','DESC')
                ->with(['categories', 'activeFlash', 'varieties'])
                ->available(true)
                ->take($take)
                ->get()
                ->toArray());
    }

    public function getNewProduct($take = 10)
    {
        return Helpers::cacheRemember('new_products', 60, function () use ($take) {
            $recommendation = $this->getByRecommendations($take,'new_products');

            return (count($recommendation) != 0)
                ? Helpers::removeVarieties($recommendation->toArray())
                : Helpers::removeVarieties(Product::query()->orderBy('created_at','DESC')
                    ->with(['categories', 'activeFlash', 'varieties'])
                    ->available(true)
                    ->take($take)
                    ->get()
                    ->toArray());
        });
    }

    public function getMostDiscount($take = 12): Collection|\Illuminate\Support\Collection|array
    {
        $discounted_items = array();

//        $startTime = microtime(true);

        // Discounted Products
        $products = DB::table('products')
            ->select(
                'id',
                'title',
                'slug',
                'discount_type',
                'discount',
                'unit_price',
                'discount_until',
                'status',
            )
            ->where('status','available')
            ->where('discount_until',">",date("Y-m-d H:i:s"))
            ->whereNotNull('discount')
            ->limit($take)->latest('id')->get();

        foreach ($products as $item) {
            $item->image = Helpers::getImages('Product',$item->id);
            $item->major_final_price = Helpers::getMajorFinalPrice($item->id);
        }

        $discounted_items = array_merge($discounted_items, $products->toArray());

        // Discounted Varieties
        $variety = DB::table('varieties as v')
            ->join('products as p','p.id','=','v.product_id')
            ->select(
                'p.id',
                'p.title',
                'p.slug',
                'v.discount_type',
                'v.discount',
                'v.price as unit_price',
                'v.discount_until',
            )
            ->where('p.status','available')
            ->where('v.discount_until',">",date("Y-m-d H:i:s"))
            ->whereNotNull('v.discount')
            ->limit($take)->get();

        foreach ($variety as $item) {
            $item->image = Helpers::getImages('Product',$item->id);
        }

        $discounted_items = array_merge($discounted_items, $variety->toArray());



//        $endTime = microtime(true);
//        $elapsedTime = $endTime - $startTime;
//        Log::info('Most Discount API: ', ['elapsed_time' => $elapsedTime]);

        return $discounted_items;
    }



}
