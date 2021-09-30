<?php

namespace App\Http\Requests\Menus\MenuItems;

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
	    'title' => 'required', 
	    'url' => 'required',
        ];

	// It's a parent private menu item.
	if ($this->menuItem->access_level == 'private' && !$this->menuItem->isParentPrivate() && $this->menuItem->canChangeAccessLevel()) {
	    // Only access level is settable.
	    $rules['access_level'] = 'required';
	}

	if ($this->menuItem->access_level != 'private' && $this->menuItem->canChangeAccessLevel()) {
	    $rules['access_level'] = 'required';
	    $rules['owned_by'] = 'required';
	}

	return $rules;
    }
}
