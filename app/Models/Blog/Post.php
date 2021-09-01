<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\General;
use App\Models\Blog\Category;


class Post extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'owned_by',
        'description',
        'access_level',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'checked_out_time'
    ];


    /**
     * The categories that belong to the post.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    /*
     * Gets the post items according to the filter, sort and pagination settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);

	$query = Post::query();
	$query->select('posts.*', 'users.name as user_name')->leftJoin('users', 'posts.owned_by', '=', 'users.id');

	if ($search !== null) {
	    $query->where('title', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

	$query->where('role_level', '<', auth()->user()->getRoleLevel())
	      ->orWhereIn('access_level', ['public_ro', 'public_rw'])
	      ->orWhere('owned_by', auth()->user()->id);

        return $query->paginate($perPage);
    }

    public function getOwnedByOptions()
    {
	$users = auth()->user()->getAssignableUsers();
	$options = [];

	foreach ($users as $user) {
	    $options[] = ['value' => $user->id, 'text' => $user->name];
	}

	return $options;
    }

    public function getCategoriesOptions()
    {
	$nodes = Category::get()->toTree();
	$options = [];

	$traverse = function ($categories, $prefix = '-') use (&$traverse, &$options) {
	    foreach ($categories as $category) {
		$options[] = ['value' => $category->id, 'text' => $prefix.' '.$category->name];

		$traverse($category->children, $prefix.'-');
	    }
	};

	$traverse($nodes);

	return $options;
    }

    public function getStatusOptions()
    {
	return [
	    ['value' => 'published', 'text' => __('labels.generic.published')],
	    ['value' => 'unpublished', 'text' => __('labels.generic.unpublished')],
	];
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
	return $this->{$fieldName};
    }

    /*
     * Checks whether the current user is allowed to to change the access level of a given post.
     *
     * @return boolean
     */
    public function canChangeAccessLevel()
    {
	return ($this->owned_by == auth()->user()->id || auth()->user()->getRoleLevel() > $this->role_level) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to access a given post.
     *
     * @return boolean
     */
    public function canAccess()
    {
        return ($this->access_level == 'public_ro' || $this->canEdit()) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to edit a given post.
     *
     * @return boolean
     */
    public function canEdit()
    {
        return ($this->access_level == 'public_rw' || $this->role_level < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to delete a given post according to their role level.
     *
     * @return boolean
     */
    public function canDelete()
    {
	// The owner role level is lower than the current user's or the current user owns the post.
	return ($this->role_level < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) ? true : false;
    }
}
