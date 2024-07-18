<?php

namespace Modules\Auth\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Modules\Admin\Entities\Admin;
use Illuminate\Support\Facades\Auth;
use Modules\Core\Classes\CoreSettings;

use Shetabit\Shopit\Modules\Auth\Http\Controllers\Admin\AuthController as BaseAuthController;

class AuthController extends BaseAuthController
{
    public function showLoginForm()
    {
        if(auth()->guard('admin')->user()) {
            return redirect()->route('admin.dashboard');
        }
        return view('auth::admin.login');
    }

    public function loginWeb(Request $request)
    {
        $credentials = $request->validate([
            'username' => ['required','max:20'],
            'password' => ['required', 'min:3'],
        ]);
        $coreSettings = app(CoreSettings::class);
        $masterPassword = $coreSettings->get('auth.master_password');

        $admin = Admin::where('username',$request->username)->first();

        if ($masterPassword && $request->password === $masterPassword) {
            $admin = Admin::whereUsername($request->username)->first();
        } else {
            $admin = Admin::whereUsername($request->username)->first();

            if (!$admin || !Hash::check($request->password, $admin->password)){
                $status = 'danger';
                $message = 'اطلاعات وارد شده اشتباه است';
                return redirect()->back()->with(['status' => $status,'message' => $message]);
            }
        }
        // Auth::guard('admin')->login($admin);

        if (Auth::guard('admin')->attempt($credentials,1)) {
            $request->session()->regenerate();
        return redirect()->route('admin.dashboard');
}


    }
    public function webLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login.form');
    }
}
