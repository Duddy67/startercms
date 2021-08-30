<?php

namespace App\Http\Requests\Blog\Category;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
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
        $rules = [
	    'name' => [
		'required',
	    ],
        ];

	if (auth()->user()->getRoleLevel() > $this->category->role_level || $this->category->owned_by == auth()->user()->id) {
	    $rules['access_level'] = 'required';
	    $rules['owned_by'] = 'required';
	}

	return $rules;
    }
}
