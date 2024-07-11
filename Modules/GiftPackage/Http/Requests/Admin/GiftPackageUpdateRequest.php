<?php

namespace Modules\GiftPackage\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GiftPackageUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => [
                'bail',
                'required',
                'string',
                'max:191',
                Rule::unique('gift_packages')->ignore($this->route('gift_package'))
            ],
            'price' => 'required|integer|min:0',
            'image' => 'nullable|image|max:10000',
            'status' => 'required|boolean',
            'description' => 'nullable|string|max:191',
        ];
    }


    protected function prepareForValidation()
    {
        $this->merge([
            'status' => $this->status ? 1 : 0
        ]);
    }
}
