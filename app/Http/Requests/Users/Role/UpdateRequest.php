<?php

namespace App\Http\Requests\Users\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Users\Role;


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
		'not_regex:/^('.implode('|', Role::getDefaultRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('roles')->ignore($this->role->id)
	    ],
        ];

	if (auth()->user()->getRoleLevel() > $this->role->role_level || $this->role->created_by == auth()->user()->id) {
	    $rules['access_level'] = 'required';
	    $rules['created_by'] = 'required';
	}

	return $rules;
    }
}