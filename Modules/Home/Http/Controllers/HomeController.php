<?php

namespace Modules\Home\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Core\Helpers\Helpers;
use Modules\Home\Entities\SiteView;
use Modules\Home\Services\HomeService;
use Modules\Product\Entities\Product;
use Shetabit\Shopit\Modules\Core\Classes\CoreSettings;
use Shetabit\Shopit\Modules\Home\Http\Controllers\HomeController as BaseHomeController;

class HomeController extends BaseHomeController
{

    #درخواست های هوم جدا شدند

    public function index_light(Request $request)
    {
//        $startTime = microtime(true);

        SiteView::store(); // count views
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front_light');
        $response = [];
        $functions =  $homeService->functions();
        $fallbacks = $homeService->fallbacks();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        foreach ($items as $key => $item){
            // اگر این کلید وجود نداشت فالبک تعریف شده را قرار میدهیم تا یک دیتایی بدهیم و فرانت را دست خالی نذاریم
            if (empty($response[$key]) && isset($fallbacks[$key]) && isset($response[$fallbacks[$key]])) {
                $response[$key] = $response[$fallbacks[$key]];
            }
        }
        $response = array_merge($response, $homeService->defaults());

        foreach ($request->query() as $key => $param) {
            switch ($key){

                case 'user':
                    $response = array_merge($response, [$key => $homeService->getUser()]);
                    break;
                case 'post':
                    $response = array_merge($response, [$key => $homeService->getPost()]);
                    break;
                case 'sliders':
                    $response = array_merge($response, [$key => $homeService->getSlider()]);
                    break;
                case 'flashes':
                    $response = array_merge($response, [$key => $homeService->getFlashes()]);
                    break;
                case 'settings':
                    $response = array_merge($response, [$key => $homeService->getSettings()]);
                    break;
                case 'advertise':
                    $response = array_merge($response, [$key => $homeService->getAdvertise()]);
                    break;
                case 'mostSales':
                    $response = array_merge($response, [$key => $homeService->getMostSales()]);
                    break;
                case 'categories':
                    $response = array_merge($response, [$key => $homeService->getCategory()]);
                    break;
                case 'suggestions':
                    $response = array_merge($response, [$key => $homeService->getSuggestions()]);
                    break;
                case 'size_values':
                    $response = array_merge($response, [$key => $homeService->getSizeValueProduct()]);
                    break;
                case 'new_products':
                    $response = array_merge($response, [$key => $homeService->getNewProduct()]);
                    break;
                case 'most_discount':
                    $response = array_merge($response, [$key => $homeService->getMostDiscount()]);
                    break;
                case 'cart_request':
                    $response = array_merge($response, [$key => $homeService->getCartFromRequest()]);
                    break;
                case 'special_categories':
                    $response = array_merge($response, [$key => $homeService->getSpecialCategory()]);
                    break;
                case 'colors':
                    $response = array_merge($response, [$key => $homeService->getColors()]);
                    break;
                case 'vip_unpublished_products':
                    $response = array_merge($response, [$key => $homeService->getVipUnpublishedProducts()]);
                    break;
                case 'discount_products':
                    $response = array_merge($response, [$key => $homeService->getDiscountProducts()]);
                    break;

            }
        }

//        $endTime = microtime(true);
//        $elapsedTime = $endTime - $startTime;
//        Log::info('Home API: ', ['elapsed_time' => $elapsedTime]);

        return response()->success(':)', compact('response'));
    }

    public function getMostDiscounts(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->mostDiscount();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }


    public function getMostSales(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->mostSales();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }

    public function getNewProducts(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->newProducts();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }

    public function getNewProductsLight($take=12)
    {
        $recommendationIds = \Modules\Product\Entities\Recommendation::query()
            ->byGroup('new_products')
            ->take($take)
            ->latest('id')
            ->get(['product_id'])
            ->pluck('product_id')
            ->toArray();

        $recommendations = DB::table('recommendations')
            ->where('group','new_products')
            ->select('product_id')
            ->orderBy('created_at','DESC')
            ->limit($take)
            ->pluck('product_id');

        if ($recommendations){
            $new_products = DB::table('products')
                ->select(
                    'id',
                    'title',
                    'status',
                    'slug',
                    'discount_type',
                    'discount',
                    'unit_price',
                )
                ->whereIn('id',$recommendations)
                ->orderBy('created_at','DESC')
                ->get();
        } else {
            $new_products = DB::table('products')
                ->select(
                    'id',
                    'title',
                    'status',
                    'slug',
                    'discount_type',
                    'discount',
                    'unit_price',
                )
                ->whereIn('status',['available','out_of_stock'])
                ->orderBy('updated_at','DESC')
                ->limit($take)
                ->get();
        }

        foreach ($new_products as $item) {
            $item->image = Helpers::getImages('Product',$item->id);
        }

        return $new_products->toArray();
//        return response()->success(':)', compact('response'));
    }

    public function getSuggestions(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->suggestions();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }

    public function getSliders(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->sliders();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }

    public function getAdvertise(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->advertize();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }


    public function getSpecialCategory(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->specialCategory();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }

    public function getDiscountProduct(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->discountProduct();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }

    public function getVipUnpublishedProducts(Request $request)
    {
        $homeService = new HomeService($request);

        $items = app(CoreSettings::class)->get('home.front');
        $response = [];
        $functions =  $homeService->VipUnpublishedProducts();

        foreach ($items as $key => $item){
            if (!isset($functions[$key]) || !$item['enabled']) {
                continue;
            }
            $response[$key] = isset($item['take']) ? $homeService->{$functions[$key]}($item['take'])
                : $homeService->{$functions[$key]}();
        }

        return response()->success(':)', compact('response'));
    }


}
