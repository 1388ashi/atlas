<?php

namespace Modules\Specification\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SpecificationSortRequest extends FormRequest
{
    public function rules()
    {
        return [
            'ids' => 'required', 'array',
            'ids.*' => 'required', 'integer', 'exists:specifications,id'
        ];
    }

}
