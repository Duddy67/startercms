<?php

namespace App\Http\Requests\Blog\Post;

use Illuminate\Foundation\Http\FormRequest;
use App\Traits\Admin\AccessLevel;


class UpdateRequest extends FormRequest
{
    use AccessLevel;

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
	    'content' => 'required',
        ];

	if ($this->post->canChangeAccessLevel()) {
	    $rules['access_level'] = 'required';
	}

	if ($this->post->canChangeStatus()) {
	    $rules['status'] = 'required';
	}

	if ($this->post->canChangeAttachments()) {
	    $rules['owned_by'] = 'required';
	}

	return $rules;
    }
}
