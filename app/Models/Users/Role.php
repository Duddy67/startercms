<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\Settings\General;


class Role extends SpatieRole 
{
    use HasFactory;

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


    /*
     * Roles that cannot be deleted nor updated.
     *
     * @return Array
     */
    public static function getDefaultRoles()
    {
        return [
	    'super-admin',
	    'admin',
	    'manager',
	    'assistant',
	    'registered'
	];
    }

    /*
     * Ids of the Roles that cannot be deleted nor updated.
     *
     * @return Array
     */
    public static function getDefaultRoleIds()
    {
        return [1,2,3,4,5];
    }

    /*
     * Role types which are allowed to create users and modify their roles.
     *
     * @return Array
     */
    public static function getAllowedRoleTypes()
    {
        return [
	    'super-admin',
	    'admin',
	    'manager'
	];
    }

    /*
     * The role hierarchy defined numerically. 
     *
     * @return Array
     */
    public static function getRoleHierarchy()
    {
	return [
	    'registered' => 1, 
	    'assistant' => 2, 
	    'manager' => 3, 
	    'admin' => 4, 
	    'super-admin' => 5
	];
    }

    /*
     * Returns the role type of a given user.
     *
     * @param \App\Models\Users\User  $user 
     * @return string
     */
    public static function getUserRoleType($user)
    {
        $roleName = $user->getRoleNames()->toArray()[0];

	if (in_array($roleName, self::getDefaultRoles())) {
	    return $roleName;
	}

	return self::defineRoleType($roleName);

    }

    /*
     * Returns the role level of a given user.
     *
     * @param \App\Models\Users\User  $user 
     * @return integer
     */
    public static function getUserRoleLevel($user)
    {
        $roleType = self::getUserRoleType($user);
	return self::getRoleHierarchy()[$roleType];
    }

    /*
     * Returns the type of a given role according its permissions.
     *
     * @param \Spatie\Permission\Models\Role or string  $role
     * @return string
     */
    public static function defineRoleType($role)
    {
	$role = (is_string($role)) ? SpatieRole::findByName($role) : $role;

	if ($role->hasPermissionTo('create-role')) {
	    return 'admin';
	}
	elseif ($role->hasPermissionTo('create-user')) {
	    return 'manager';
	}
	elseif ($role->hasPermissionTo('access-admin')) {
	    return 'assistant';
	}
	else {
	    return 'registered';
	}
    }

    /*
     * Returns the roles that the current user is allowed to assign to an other user.
     *
     * @param  \App\Models\Users\User $user 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getAssignableRoles($user)
    {
	// Check first if the current user is editing their own user account.
	if (auth()->user()->id == $user->id) {
	    // Only display the user's role as users cannot change their own role.
	    return Role::where('name', $user->getRoleNames()->toArray()[0])->get();
	}

	// Get the current user's role type.
        $roleType = self::getUserRoleType(auth()->user());

	if (!in_array($roleType, self::getAllowedRoleTypes())) {
	    // Returns an empty collection.
	    return new \Illuminate\Database\Eloquent\Collection();
	}

	if ($roleType == 'manager') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->whereIn('name', ['create-role', 'create-user']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	elseif ($roleType == 'admin') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->whereIn('name', ['create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	elseif ($roleType == 'super-admin') {
	    $roles = Role::whereNotIn('name', ['super-admin'])->get();
	}

	return $roles;
    }

    /*
     * Used only during the very first registration (the super-user) in the CMS.
     *
     * @return void
     */
    public function createDefaultRoles()
    {
        if (Role::whereIn('name', self::getDefaultRoles())->doesntExist()) {
	    $date = Carbon::now();

	    Role::insert([
		['name' => 'super-admin', 'guard_name' => 'web', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'admin', 'guard_name' => 'web', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'manager', 'guard_name' => 'web', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'assistant', 'guard_name' => 'web', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'registered', 'guard_name' => 'web', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()]
	    ]);
	}
    }

    /*
     * Gets the role items according to the filter, sort and pagination settings.
     *
     * @param  Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);

	$query = Role::query();
	$query->select('roles.*', 'users.name as user_name')->leftJoin('users', 'roles.created_by', '=', 'users.id');

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        return $this->{$fieldName};
    }

    /*
     * Checks whether the current user is allowed to access a given role according to their role level.
     *
     * @return boolean
     */
    public function canAccess()
    {
        if ($this->access_level == 'public_ro' || $this->canEdit()) {
	    return true;
	}

	return false;
    }

    /*
     * Checks whether the current user is allowed to edit a given role according to their role level.
     *
     * @return boolean
     */
    public function canEdit()
    {
        if ($this->access_level == 'public_rw' || $this->role_level < auth()->user()->getRoleLevel() || $this->created_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }

    /*
     * Checks whether the current user is allowed to delete a given role according to their role level.
     *
     * @return boolean
     */
    public function canDelete()
    {
        if (auth()->user()->hasRole('super-admin')) {
	    return true;
	}

	// The owner role level is lower than the current user's or the current user owns the role.
	if ($this->role_level < auth()->user()->getRoleLevel() || $this->created_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }
}
