<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\General;
use App\Models\Blog\Category;
use App\Models\Users\Group;
use App\Traits\Admin\AccessLevel;
use App\Traits\Admin\CheckInCheckOut;


class Post extends Model
{
    use HasFactory, AccessLevel, CheckInCheckOut;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'slug',
        'status',
        'owned_by',
        'content',
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

    /**
     * The groups that belong to the post.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Delete the model from the database (override).
     *
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete()
    {
        $this->categories()->detach();
        $this->groups()->detach();

        parent::delete();
    }

    /*
     * Gets the post items according to the filter, sort and pagination settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);
        $ownedBy = $request->input('owned_by', null);
        $groups = $request->input('groups', []);
        $categories = $request->input('categories', []);

	$query = Post::query();
	$query->select('posts.*', 'users.name as user_name')->leftJoin('users', 'posts.owned_by', '=', 'users.id');
	// Join the role tables to get the owner's role level.
	$query->join('model_has_roles', 'posts.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

	if ($search !== null) {
	    $query->where('posts.title', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

	if ($ownedBy !== null) {
	    $query->whereIn('posts.owned_by', $ownedBy);
	}

	if (!empty($groups)) {
	    $query->whereHas('groups', function($query) use($groups) {
		$query->whereIn('id', $groups);
	    });
	}

	if (!empty($categories)) {
	    $query->whereHas('categories', function($query) use($categories) {
		$query->whereIn('id', $categories);
	    });
	}

	$query->where(function($query) {
	    $query->where('roles.role_level', '<', auth()->user()->getRoleLevel())
		  ->orWhereIn('posts.access_level', ['public_ro', 'public_rw'])
		  ->orWhere('posts.owned_by', auth()->user()->id);
	});

        $groupIds = auth()->user()->getGroupIds();

	if(!empty($groupIds)) {
	    $query->orWhereHas('groups', function ($query)  use ($groupIds) {
		$query->whereIn('id', $groupIds);
	    });
	}

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
	$userGroupIds = auth()->user()->getGroupIds();

	$traverse = function ($categories, $prefix = '-') use (&$traverse, &$options, $userGroupIds) {
	    foreach ($categories as $category) {
	        $isUserInGroup = (!empty(array_intersect($userGroupIds, $category->getGroupIds()))) ? true : false;
		$extra = ($category->access_level == 'private' && $category->owned_by != auth()->user()->id && !$isUserInGroup) ? ['disabled'] : [];
		$options[] = ['value' => $category->id, 'text' => $prefix.' '.$category->name, 'extra' => $extra];

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
        if ($fieldName == 'groups') {
	    return $this->groups->pluck('id')->toArray();
	}

        if ($fieldName == 'categories') {
	    return $this->categories->pluck('id')->toArray();
	}

	return $this->{$fieldName};
    }

    public function getPrivateCategories()
    {
        return $this->categories()->where([
					  ['blog_categories.access_level', '=', 'private'], 
					  ['blog_categories.owned_by', '!=', auth()->user()->id]
				      ])->pluck('blog_categories.id')->toArray();
    }
}
