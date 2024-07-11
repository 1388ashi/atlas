<?php

namespace Modules\Product\Http\Controllers\Admin;

use Modules\Product\Exports\ProductExport;
use Shetabit\Shopit\Modules\Product\Http\Controllers\Admin\ProductController as BaseProductController;
use Carbon\Carbon;
use Exception;
use Hekmatinasser\Verta\Verta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attribute\Entities\Attribute;
use Modules\Category\Entities\Category;
use Modules\Color\Entities\Color;
use Modules\Core\Classes\CoreSettings;
use Modules\Core\Classes\Tag;
use Modules\Core\Helpers\Helpers;
use Modules\Product\Entities\Gift;
use Modules\Product\Entities\ListenCharge;
use Modules\Product\Entities\Product;
use Modules\Product\Entities\Variety;
use Modules\Product\Http\Requests\Admin\ProductStoreRequest;
use Modules\Product\Http\Requests\Admin\ProductUpdateRequest;
use Modules\Product\Jobs\SendProductAvailableNotificationJob;
use Modules\SizeChart\Entities\SizeChartType;
use Modules\Specification\Entities\Specification;
use Modules\Unit\Entities\Unit;
use Shetabit\Shopit\Modules\Core\Exports\ModelExport;
use Throwable;

class ProductController extends BaseProductController
{
    public function excel(Request $request, $id)
    {
        $product = Product::withCommonRelations()->with('varieties.product')->findOrFail($id);
        switch ($request->type) {
            case 1:
                return Excel::download((new ProductExport($product)),
                    'product-' . $id . '.xlsx');
        }
    }

    public function index(): JsonResponse
    {
        $products = Product::with('unit','brand', 'categories')->latest('id');
        if ($temp = Helpers::hasCustomSearchBy('approved_at')) {
            $products->whereNotNull('approved_at');
        } else if ($temp === "0") {
            $products->whereNull('approved_at');
        }

        if ($temp = Helpers::hasCustomSearchBy('category_id')) {
            $products->whereHas('categories', function($item) use ($temp){
                $item->where('id', $temp)->orWhere('parent_id', $temp);
            });
        }

        $products = $products->filters()
            ->addSelect(DB::raw('products.*, MIN(varieties.price) AS main_price, SUM(stores.balance) AS quantity'))
            ->leftJoin('varieties', function($query) {
                $query->on('products.id', '=','varieties.product_id');
                $query->whereNull('varieties.deleted_at');
            })
            ->leftJoin('stores', 'varieties.id', '=', 'stores.variety_id')
            ->groupBy('varieties.product_id')->paginateOrAll(app(CoreSettings::class)->get('product.admin.pagination', 10));
        $statusCounts = Product::getStatusCounts();

        return response()->success('لیست تمامی محصولات', compact('products', 'statusCounts'));
    }

      /**
     * Store a newly created resource in storage.
     * @param ProductStoreRequest $request
     * @param Product $product
     * @return JsonResponse
     * @throws Throwable
     */
    public function store(ProductStoreRequest $request, Product $product): JsonResponse
    {
        // dd($request->product["video"]);
        DB::beginTransaction();
        try {
            $product->fill($request->product);
            $product->checkStatusChanges($request->product);
            $product->unit()->associate($request->product['unit_id']);
            $product->brand()->associate($request->product['brand_id']);
            $product->save();
            if ($request->filled('product.images')) {
                $product->addImages($request->product['images']);
            }

            if ($request->filled('product.video_cover')) {
                $product->addVideoCover([$request->product['video_cover']]);
            }

            if ($request->filled('product.video')) {
                $product->addVideo([$request->product['video']]);
            }


            $product->attachTags($request->product['tags']);
            $product->assignSpecifications($request->product);
            $product->assignSizeChart($request->product);
            /**
             * Insert Product Variety
             * Varieties are created with the products
             * @see Product method storeVariety
             */
            $product->assignVariety($request->product);
            $product->assignGifts($request->product);

            if (!empty($request->product['categories'])) {
                $product->categories()->attach($request->product['categories']);
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            return response()->error('مشکلی در ثبت محصول به وجود آمده: ' . $e->getMessage(), $e->getTrace());
        }

        $product = $product->loadCommonRelations();
        /** بروزرسانی تاریخ  برای سایت مپ */
        $product->categories()->touch();

        return response()->success('محصول با موفقیت ایجاد شد', compact('product'));
    }

     /**
     * Update the specified resource in storage.
     * @param ProductUpdateRequest $request
     * @param Product $product
     * @return JsonResponse
     * @throws Throwable
     */
    public function update(ProductUpdateRequest $request, Product $product)
    {
        try {
            DB::beginTransaction();
            $oldStatus = $product->status;
            $product->fill($request->product);
            $product->checkStatusChanges($request->product);
            $product->unit()->associate($request->product['unit_id']);
            $product->brand()->associate($request->product['brand_id']);
            $product->save();
            $product->syncTags($request->product['tags']);
            $product->updateImages($request->product['images'] ?? []);


            if(!($request->product['video_cover'] ?? null) && $product->hasMedia('video_cover')){
                $product->getFirstMedia('video_cover')->delete();

            }

            if(!($request->product['video'] ?? null) && $product->hasMedia('video')){
                $product->getFirstMedia('video')->delete();

            }
            // new_video_cover
            if($request->product['new_video_cover'] ?? null){
                $product->updateVideoCover([$request->product['new_video_cover']]);

            }

            // new_video
            if($request->product['new_video'] ?? null){
                $product->updateVideo([$request->product['new_video']]);

            }

            // video deleted


            $product->assignSpecifications($request->product);
            $product->assignSizeChart($request->product);
            /**
             * Insert Product Variety
             * Varieties are created with the products
             * @see Product method storeVarietyf
             */
            $product->assignVariety($request->product, true);
            $product->assignGifts($request->product);

            if (!empty($request->product['categories'])) {
                $product->categories()->sync($request->product['categories']);

            }


//            if (($request->product['listen_charge'])
//                && ($oldStatus != Product::STATUS_AVAILABLE)
//                && ($product->status == Product::STATUS_AVAILABLE)
//            ){
//                foreach ($product->varieties as $variety){
//                    SendProductAvailableNotificationJob::dispatchNow($variety);
//                }
//            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error($e->getTraceAsString());
            return response()->error(' مشکلی در بروزرسانی محصول به وجود آمده :   ' . $e->getMessage());
        }

        $product->loadCommonRelations();

        return response()->success('محصول با موفقیت بروزرسانی شد', compact('product'));
    }

    public function create()
    {
        $categories = Category::query()->where('status',1)
            ->with('attributes.values', 'brands', 'specifications.values')
            ->with(['children' => function ($query) {
                $query->with('attributes.values', 'brands', 'specifications.values')->where('status',1);
            }])
            ->with(['specifications' => function ($query) {
                $query->with('values');
                $query->latest('order');
            }])->parents()->orderBy('priority')
            ->get();
        $units = Unit::active()->get(['id', 'name']);
        $tags = Tag::get(['id', 'name']);
        $colors = Color::all();
        $public_specifications = Specification::active()->where('public', 1)->with('values')->latest('order')->get();
        $all_attributes = Attribute::with('values')->get();
        if (app(CoreSettings::class)->get('size_chart.type')) {
            $size_chart_types = SizeChartType::query()->filters()->latest()->get();
        } else {
            $size_chart_types = [];
        }
        $data = compact('categories', 'units', 'tags', 'colors',
            'public_specifications', 'all_attributes', 'size_chart_types');

        $coreSettings = app(CoreSettings::class);
        if ($coreSettings->get('product.gift.active')) {
            $data['gifts'] = Gift::all();
        }

        return response()->success('', $data);
    }
}
