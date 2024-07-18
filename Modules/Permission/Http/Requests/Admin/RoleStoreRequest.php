<?php

namespace Modules\Permission\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Core\Entities\Role;
use Modules\Core\Helpers\Helpers;
use Shetabit\Shopit\Modules\Permission\Http\Requests\Admin\RoleStoreRequest as BaseRoleStoreRequest;

class RoleStoreRequest extends BaseRoleStoreRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'label' => 'nullable|string|min:2|max:100',
            'guard_name' => 'nullable|string|min:2|max:100',
            'permissions' => 'required|array',
            'permissions.*' => 'required|integer|exists:permissions,id'
        ];
    }

    protected function passedValidation()
    {
        if ($this->guard_name == null){
            $this->merge(['guard_name' => 'admin-api']);
        }

        $role = Role::query()->where(['name' => $this->name, 'guard_name' => $this->guard_name]);
        if ($role->exists()){
            return throw Helpers::makeValidationException('این نقش موجود است');
        }
    }
    public function authorize(): bool
    {
        return true;
    }
}
