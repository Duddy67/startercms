<?php

namespace App\Models\Users;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Admin\RolesPermissions;
use Spatie\Permission\Models\Role;
use App\Models\Users\Group;
use App\Models\Cms\Document;
use App\Models\Settings\General;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, RolesPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
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
     * The groups that belong to the user.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * The user's documents.
     */
    public function documents()
    {
        return $this->HasMany(Document::class, 'item_id');
    }

    /*
     * Override.
     */
    public function delete()
    {
	foreach ($this->documents as $document) {
	    // Ensure the linked file is removed from the server, (see the Document delete() function).
	    $document->delete();
	}

        $this->groups()->detach();

        parent::delete();
    }

    /*
     * Gets the user items according to the filter, sort and pagination settings.
     *
     * @param  Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $sortedBy = $request->input('sorted_by', null);
        $roles = $request->input('roles', []);
        $groups = $request->input('groups', []);
        $search = $request->input('search', null);

	$query = User::whereHas('roles', function($query) use($roles) {
	    if (!empty($roles)) {
	        $query->whereIn('name', $roles);
	    }
	});

	if (!empty($groups)) {
	    $query->whereHas('groups', function($query) use($groups) {
		$query->whereIn('id', $groups);
	    });
	}

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

        return $query->paginate($perPage);
    }

    /*
     * Builds the options for the 'role' select field.
     *
     * @param  \App\Models\Users\User $request (optional)
     * @return Array
     */
    public function getRoleOptions($user = null)
    {
	$roles = auth()->user()->getAssignableRoles($user);
	$options = [];

	foreach ($roles as $role) {
	    $options[] = ['value' => $role->name, 'text' => $role->name];
	}

	return $options;
    }

    /*
     * Builds the options for the 'groups' select field.
     *
     * @param  \App\Models\Users\User $user (optional)
     * @return Array
     */
    public function getGroupsOptions($user = null)
    {
        $groups = Group::all();
	$options = [];

	foreach ($groups as $group) {
	    $options[] = ['value' => $group->id, 'text' => $group->name];
	}

	return $options;
    }

    /*
     * Builds the options for the 'roles' select field, (used with filters).
     *
     * @return Array
     */
    public function getRolesOptions()
    {
        $roles = Role::all()->pluck('name')->toArray();

	foreach ($roles as $role) {
	    $options[] = ['value' => $role, 'text' => $role];
	}

	return $options;
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        if ($fieldName == 'role') {
	    return $this->getRoleName();
	}

        if ($fieldName == 'groups') {
	    return $this->groups->pluck('id')->toArray();
	}

	return null;
    }

    /*
     * Returns a relative url to the user's photo thumbnail.
     *
     * @return string
     */
    public function getThumbnail()
    {
        $document = Document::where(['item_type' => 'user', 'field' => 'photo', 'item_id' => $this->id])->orderBy('created_at', 'desc')->first();

	if ($document) {
	    return $document->getThumbnailUrl();
	}

	// Returns a default user image.
	return '/images/user.png';
    }

    /*
     * Checks whether the current user is allowed to update a given user according to their role type.
     *
     * @param  \App\Models\Users\User $user
     * @return boolean
     */
    public function canUpdate($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	$hierarchy = $this->getRoleHierarchy();
        // Users can only update users lower in the hierarchy.
	if ($hierarchy[$this->getRoleType()] > $hierarchy[$this->getUserRoleType($user)]) {
	    return true;
	}

	return false;
    }

    /*
     * Checks whether the current user is allowed to delete a given user according to their role type.
     *
     * @param  \App\Models\Users\User $user
     * @return boolean
     */
    public function canDelete($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	// Users cannot delete their own account.
        if ($this->id == $user->id) {
	    return false;
	}

	$hierarchy = $this->getRoleHierarchy();
        // Users can only delete users lower in the hierarchy.
	if ($hierarchy[$this->getRoleType()] > $hierarchy[$this->getUserRoleType($user)]) {
	    return true;
	}

	return false;
    }

    /*
     * Returns the user's role name.
     *
     * @return string
     */
    public function getRoleName()
    {
        return $this->getRoleNames()->toArray()[0];
    }

    /*
     * Returns the user's role level.
     *
     * @return integer
     */
    public function getRoleLevel()
    {
        $roleType = $this->getRoleType();

	return $this->getRoleHierarchy()[$roleType];
    }

    /*
     * Returns the user's role type.
     *
     * @return string
     */
    public function getRoleType()
    {
        if (in_array($this->getRoleName(), $this->getDefaultRoles())) {
	    return $this->getRoleName();
	}
	else {
	    return $this->defineRoleType($this->getRoleName());
	}
    }

    /*
     * Blade directive
     *
     * @param  \Spatie\Permission\Models\Permission $permission
     * @return boolean
     */
    public function isAllowedTo($permission)
    {
	return $this->hasRole('super-admin') || $this->hasPermissionTo($permission);
    }

    /*
     * Blade directive
     *
     * @param  \Spatie\Permission\Models\Permission $permission
     * @return boolean
     */
    public function isAllowedToAny($permission)
    {
	return $this->hasRole('super-admin') || $this->hasAnyPermission($permission);
    }

    /*
     * Blade directive
     *
     * @param  \Spatie\Permission\Models\Permission $permission
     * @return boolean
     */
    public function isAllowedToAll($permissions)
    {
	return $this->hasRole('super-admin') || $this->hasAllPermissions($permissions);
    }

    /*
     * Blade directive
     *
     * @return boolean
     */
    public function canAccessAdmin()
    {
        return in_array($this->getRoleType(), ['super-admin', 'admin', 'manager', 'assistant']);
    }
}
