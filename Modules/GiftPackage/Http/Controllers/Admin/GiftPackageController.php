<?php

namespace Modules\GiftPackage\Http\Controllers\Admin;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB as DB;
use Illuminate\Support\Facades\Log;
use Modules\Area\Entities\City;
use Modules\GiftPackage\Entities\GiftPackage;
use Modules\GiftPackage\Http\Requests\Admin\GiftPackageCityAssignRequest;
use Modules\GiftPackage\Http\Requests\Admin\GiftPackageStoreRequest;
use Modules\GiftPackage\Http\Requests\Admin\GiftPackageUpdateRequest;

class GiftPackageController extends Controller
{
    public function index()
    {
        $gift_packages = GiftPackage::withCommonRelations()->orderBy('id','ASC')->filters()->paginateOrAll();

        return response()->success('', compact('gift_packages'));
    }


    public function store(GiftPackageStoreRequest $request)
    {
        DB::beginTransaction();
        try {
            $gift_package = GiftPackage::create($request->all());

            if ($request->hasFile('image')) {
                $gift_package->addImage($request->image);
            }
            $gift_package->load('media');


            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception->getTraceAsString());
            return response()->error('مشکلی در ثبت بسته بندی هدیه به وجود آمده است: ' . $exception->getMessage(), $exception->getTrace());
        }
        $gift_package->loadCommonRelations();

        return response()->success(' بسته بندی هدیه با موفقیت ثبت شد', compact('gift_package'));
    }


    public function show($id)
    {
        $gift_package = GiftPackage::withCommonRelations()->findOrFail($id);

        return response()->success('سرویس بسته بندی هدیه با موفقیت دریافت شد', compact('gift_package'));
    }


    public function sort(Request $request)
    {
        $request->validate([
            'orders' => 'required|array',
            'orders.*' => 'required|exists:gift_packages,id'
        ]);
        $order = 99;
        foreach ($request->input('orders') as $itemId) {
            $model = GiftPackage::find($itemId);
            if (!$model) {
                continue;
            }
            $model->order = $order--;
            $model->save();
        }

        return response()->success('مرتب سازی با موفقیت انجام شد');
    }

    public function update(GiftPackageUpdateRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            /** @var GiftPackage $gift_package */
            $gift_package = GiftPackage::findOrFail($id);

            $gift_package->fill($request->all());
            if ($request->hasFile('image')) {
                $gift_package->addImage($request->image);
            }
            $gift_package->save();


            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();
            Log::error($exception->getTraceAsString());
            return response()->error('مشکلی در به روزرسانی بسته بندی هدیه به وجود آمده است: ' . $exception->getMessage(), $exception->getTrace());
        }
        $gift_package->loadCommonRelations();

        return response()->success(' بسته بندی هدیه با موفقیت به روزرسانی شد', compact('gift_package'));
    }



    public function destroy($id)
    {
        $gift_package = GiftPackage::findOrFail($id);

        $gift_package->delete();

        return response()->success(' بسته بندی هدیه با موفقیت حذف شد');
    }
}
