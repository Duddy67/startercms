<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\General;
use Illuminate\Support\Facades\Auth;
use App\Models\Blog\Post;
use Kalnoy\Nestedset\NodeTrait;
use App\Models\Users\Group;
use App\Traits\Admin\TreeAccessLevel;
use App\Traits\Admin\CheckInCheckOut;


class Category extends Model
{
    use HasFactory, NodeTrait, TreeAccessLevel, CheckInCheckOut;

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

    /**
     * The groups that belong to the category.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class, 'blog_category_group');
    }

    /**
     * The group ids the category is in.
     *
     * @return array
     */
    public function getGroupIds()
    {
        return $this->groups()->pluck('groups.id')->toArray();
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
	    return Category::select('blog_categories.*', 'users.name as user_name')->leftJoin('users', 'blog_categories.owned_by', '=', 'users.id')->defaultOrder()->get()->toTree();
	}
    }

    public function getUrl()
    {
        return '/category/'.$this->id.'/'.$this->slug;
    }

    public function getPosts($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);

	$groups = [];

	if (Auth::check()) {
	    $groups = auth()->user()->getGroupIds();
	}

	$query = Post::query();
	$query->select('posts.*', 'users.name as user_name')->leftJoin('users', 'posts.owned_by', '=', 'users.id');
	// Join the role tables to get the owner's role level.
	$query->join('model_has_roles', 'posts.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

	// Get only the posts related to this category. 
	$query->whereHas('categories', function ($query) {
	    $query->where('category_id', $this->id);
	});

	if ($search !== null) {
	    $query->where('posts.title', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

    public function getParentIdOptions()
    {
	$nodes = Category::get()->toTree();
	$options = [];
	// Defines the state of the current instance.
	$isNew = ($this->id) ? false : true;

	$traverse = function ($categories, $prefix = '-') use (&$traverse, &$options, $isNew) {

	    foreach ($categories as $category) {
	        if (!$isNew && $this->access_level != 'private') {
		    // A non private category cannot be a private category's children.
		    $extra = ($category->access_level == 'private') ? ['disabled'] : [];
		}
		elseif (!$isNew && $this->access_level == 'private' && $category->access_level == 'private') {
		      // Only the category's owner can access it.
		      $extra = ($category->owned_by == auth()->user()->id) ? [] : ['disabled'];
		}
		elseif ($isNew && $category->access_level == 'private') {
		      // Only the category's owner can access it.
		      $extra = ($category->owned_by == auth()->user()->id) ? [] : ['disabled'];
		}
		else {
		    $extra = [];
		}

		$options[] = ['value' => $category->id, 'text' => $prefix.' '.$category->name, 'extra' => $extra];

		$traverse($category->children, $prefix.'-');
	    }
	};

	$traverse($nodes);

	return $options;
    }

    public function canView()
    {
        // Check if the current user and the category share one or more groups.
        $groups = (Auth::check()) ? array_intersect($this->getGroupIds(), auth()->user()->getGroupIds()) : [];
        $userId = (Auth::check()) ? auth()->user()->id : null;

	return (in_array($this->access_level, ['public_ro', 'public_rw']) ||
		($this->access_level == 'private' && $this->owned_by == $userId) ||
		!empty($groups)) ? true : false;
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
        if ($fieldName == 'groups') {
	    return $this->groups->pluck('id')->toArray();
	}

	return $this->{$fieldName};
    }
}
