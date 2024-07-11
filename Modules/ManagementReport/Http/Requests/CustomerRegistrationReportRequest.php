<?php

namespace Modules\ManagementReport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegistrationReportRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
//            'start_date' => 'required',
//            'end_date' => 'required',
            'type' => 'required|in:year,month,week',
            'year_offset' => 'required_if:type,year,month',
            'month' => 'required_if:type,month',
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
