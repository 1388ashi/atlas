<?php

namespace Modules\GiftPackage\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GiftPackageStoreRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'bail|required|string|max:191|unique:gift_packages',
            'price' => 'required|integer|min:0',
            'description' => 'nullable|string|max:191',
            'image' => 'required|image|max:10000',
            'status' => 'required|boolean',
            
        ];
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'status' => $this->status ? 1 : 0
        ]);
    }
}
