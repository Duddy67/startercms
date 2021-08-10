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
     * Role types which are allowed to create users (and groups) and modify their roles.
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
     * Returns the type of a role according to its permissions.
     *
     * @return string
     */
    public function defineRoleType()
    {
	if ($this->hasPermissionTo('create-role')) {
	    return 'admin';
	}
	elseif ($this->hasPermissionTo('create-user')) {
	    return 'manager';
	}
	elseif ($this->hasPermissionTo('access-admin')) {
	    return 'assistant';
	}
	else {
	    return 'registered';
	}
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
     * Returns only the users with a super-admin or admin role or admin role type.
     *
     * @return array
     */
    public static function getCreatedByOptions()
    {
	$users = \App\Models\Users\User::whereHas('roles', function ($query) {
	    $query->whereIn('name', ['super-admin', 'admin'])->orWhere('role_type', 'admin');
	})->get();

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
	// The owner role level is lower than the current user's or the current user owns the role.
	if ($this->role_level < auth()->user()->getRoleLevel() || $this->created_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }
}