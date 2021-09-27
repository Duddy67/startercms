<?php

namespace App\Http\Requests\Menus\Menus;

use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
	    'title' => 'required',
	    'status' => 'required',
	    'access_level' => 'required',
	    'owned_by' => 'required',
        ];
    }
}
