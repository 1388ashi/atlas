<?php

namespace Modules\Permission\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Core\Entities\Role;
use Modules\Core\Helpers\Helpers;

class RoleUpdateRequest extends FormRequest
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
        $id = Helpers::getModelIdOnPut('role');

        if ($this->guard_name == null){
            $this->merge(['guard_name' => 'admin-api']);
        }

        $role = Role::query()->where('id','<>', $id)
            ->where(['name' => $this->name, 'guard_name' => $this->guard_name]);
        if ($role->exists()){
            return throw Helpers::makeValidationException('این نقش موجود است');
        }
    }
}
