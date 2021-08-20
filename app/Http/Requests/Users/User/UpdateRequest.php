<?php

namespace App\Http\Requests\Users\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;


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
	    'name' => 'bail|required|between:5,25|regex:/^[\pL\s\-]+$/u',
	    'email' => ['bail', 'required', 'email',
			Rule::unique('users')->ignore($this->user->id)
	    ],
	    'password' => 'nullable|confirmed|min:8'
        ];

	if (auth()->user()->id != $this->user->id && !$this->user->isRolePrivate()) {
	    $rules['role'] = 'required';
	}

	return $rules;
    }
}
