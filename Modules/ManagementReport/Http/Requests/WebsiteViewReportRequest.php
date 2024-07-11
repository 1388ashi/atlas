<?php

namespace Modules\ManagementReport\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WebsiteViewReportRequest extends FormRequest
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
            'type' => 'required|in:year,month,week,day',
            'year_offset' => 'required_if:type,year,month',
            'month' => 'required_if:type,month',
            'date' => 'required_if:type,day',
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
