<?php

namespace Modules\Auth\Http\Requests\Admin;

// use Shetabit\Shopit\Modules\Auth\Http\Requests\Admin\AdminLoginRequest as BaseAdminLoginRequest;

class AdminLoginRequest
{
    public function rules()
    {
        return [
            'username' => 'required|min:0|max:20',
            'password' => 'required',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }
}
