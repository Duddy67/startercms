<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\General;
use App\Models\Blog\Post;
use Kalnoy\Nestedset\NodeTrait;


class Category extends Model
{
    use HasFactory, NodeTrait;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blog_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'owned_by',
        'description',
        'access_level',
        'parent_id',
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
     * The posts that belong to the category.
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    /*
     * Gets the category items as a tree.
     */
    public function getItems($request)
    {
        $search = $request->input('search', null);

	if ($search !== null) {
	    return Category::where('name', 'like', '%'.$search.'%')->get();
	}
	else {
	    return Category::select('blog_categories.*', 'users.name as user_name')->leftJoin('users', 'blog_categories.owned_by', '=', 'users.id')->get()->toTree();
	}
    }

    public function getStatusOptions()
    {
	return [
	    ['value' => 'published', 'text' => __('labels.generic.published')],
	    ['value' => 'unpublished', 'text' => __('labels.generic.unpublished')],
	];
    }

    public function getParentIdOptions()
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

    public function getOwnedByOptions()
    {
	$users = auth()->user()->getAssignableUsers(['assistant', 'registered']);
	$options = [];

	foreach ($users as $user) {
	    $options[] = ['value' => $user->id, 'text' => $user->name];
	}

	return $options;
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
	return $this->{$fieldName};
    }

    /*
     * Checks whether the current user is allowed to to change the access level of a given category.
     *
     * @return boolean
     */
    public function canChangeAccessLevel()
    {
	return ($this->owned_by == auth()->user()->id || auth()->user()->getRoleLevel() > $this->role_level) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to access a given category.
     *
     * @return boolean
     */
    public function canAccess()
    {
        return ($this->access_level == 'public_ro' || $this->canEdit()) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to edit a given category.
     *
     * @return boolean
     */
    public function canEdit()
    {
        return ($this->access_level == 'public_rw' || $this->role_level < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to delete a given category according to their role level.
     *
     * @return boolean
     */
    public function canDelete()
    {
	// The owner role level is lower than the current user's or the current user owns the category.
	return ($this->role_level < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) ? true : false;
    }
}
