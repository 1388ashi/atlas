<?php

namespace Modules\ManagementReport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategorizedReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'type' => 'required|in:province,city,gender',
//            'year_offset' => 'required_if:type,year,month',
            'province' => 'required_if:type,city',
            'start_date' => 'required',
            'end_date' => 'required',
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
