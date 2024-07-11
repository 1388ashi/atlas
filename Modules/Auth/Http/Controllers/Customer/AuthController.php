<?php

namespace Modules\Auth\Http\Controllers\Customer;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Http\Requests\Customer\CustomerRegisterLoginRequest;
use Modules\Auth\Http\Requests\Customer\CustomerSendTokenRequest;
use Modules\Core\Helpers\Helpers;
use Modules\Customer\Entities\Customer;
use Modules\Customer\Events\SmsVerify;
use Shetabit\Shopit\Modules\Auth\Http\Controllers\Customer\AuthController as BaseAuthController;
use Exception;

class AuthController extends BaseAuthController
{

    public function registerLogin(CustomerRegisterLoginRequest $request): JsonResponse
    {

        $request->validate([
            'sdlkjcvisl' => 'required|string'
        ]);

        if (!$request->has('sdlkjcvisl') || $request->sdlkjcvisl != 'uikjdknfs') {
            throw Helpers::makeValidationException('خطا در تایید کد کپچا');
        }

        try {
            $customer = Customer::where('mobile', $request->mobile)->first();
            if ($customer && !$customer->isActive()) {
                return response()->error('حساب شما غیر فعال است. لطفا با پشتیبانی تماس حاصل فرمایید.');
            }
            $status = ($customer && $customer->password) ? 'login' : 'register';

            if ($status === 'register') {
                $result = event(new SmsVerify($request->mobile));
                if ($result[0]['status'] != 200) {
                    return response()->error('ارسال کدفعال سازی ناموفق بود.لطفا دوباره تلاش کنید', null);
                }
            }

            $mobile = $request->mobile;

            return response()->success('بررسی وضعیت ثبت نام مشتری', compact('status', 'mobile'));
        } catch(Exception $exception) {
            Log::error($exception->getTraceAsString());
            return response()->error(
                'مشکلی در برنامه بوجود آمده است. لطفا با پشتیبانی تماس بگیرید: ' . $exception->getMessage(),
                $exception->getTrace(),
                500
            );
        }
    }

    public function sendToken(CustomerSendTokenRequest $request): JsonResponse
    {
        $request->validate([
            'sdlkjcvisl' => 'required|string'
        ]);

        if (!$request->has('sdlkjcvisl') || $request->sdlkjcvisl != 'uikjdknfs') {
            throw Helpers::makeValidationException('خطا در تایید کد کپچا');
        }


        try {
            $result = event(new SmsVerify($request->mobile));

            if ($result[0]['status'] != 200) {
                throw new Exception($result[0]['message']);
            }
            $mobile = $request->mobile;
            return response()->success('بررسی وضعیت ثبت نام مشتری', compact('mobile'));
        } catch(Exception $exception) {
            Log::error($exception->getTraceAsString());
            return response()->error(
                'مشکلی در برنامه بوجود آمده است. لطفا با پشتیبانی تماس بگیرید: ' . $exception->getMessage(),
                $exception->getTrace(),
                422
            );
        }
    }
}
