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
     * The posts that belong to the category.
     */
    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }

    /*
     * Gets the post items according to the filter, sort and pagination settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);

	$query = Category::query();
	$query->select('blog_categories.*', 'users.name as user_name')->leftJoin('users', 'blog_categories.owned_by', '=', 'users.id');

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
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
