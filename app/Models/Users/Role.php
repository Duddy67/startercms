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
	elseif ($this->hasPermissionTo('access-dashboard')) {
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
		['name' => 'super-admin', 'guard_name' => 'web', 'role_type' => 'super-admin', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'admin', 'guard_name' => 'web', 'role_type' => 'admin', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'manager', 'guard_name' => 'web', 'role_type' => 'manager', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'assistant', 'guard_name' => 'web', 'role_type' => 'assistant', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()],
		['name' => 'registered', 'guard_name' => 'web', 'role_type' => 'registered', 'created_at' => $date->toDateTimeString(), 'updated_at' => $date->toDateTimeString()]
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
	$query->select('roles.*', 'users.name as user_name')->leftJoin('users', 'roles.owned_by', '=', 'users.id');

	if ($search !== null) {
	    $query->where('roles.name', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

    /*
     * Returns only the users with a super-admin or admin role type.
     *
     * @return array
     */
    public static function getOwnedByOptions()
    {
        // Get only users with admin role types.
	$users = auth()->user()->getAssignableUsers(['manager', 'assistant', 'registered']);
	$options = [];

	foreach ($users as $user) {
	    $options[] = ['value' => $user->id, 'text' => $user->name];
	}

	return $options;
    }

    public static function getRoleTypeOptions()
    {
        $roles = [
            ['value' => 'registered', 'text' => __('labels.roles.registered')],
            ['value' => 'assistant', 'text' => __('labels.roles.assistant')],
            ['value' => 'manager', 'text' => __('labels.roles.manager')]
	];

	if (auth()->user()->getRoleName() == 'super-admin') {
	    $roles[] = ['value' => 'admin', 'text' => __('labels.roles.admin')];
	}

	return $roles;
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        return $this->{$fieldName};
    }

    /*
     * Checks whether the current user is allowed to to change the access level of a given role.
     *
     * @return boolean
     */
    public function canChangeAccessLevel()
    {
	return ($this->owned_by == auth()->user()->id || auth()->user()->getRoleLevel() > $this->role_level) ? true: false;
    }

    /*
     * Checks whether the current user is allowed to access a given role according to their role level.
     *
     * @return boolean
     */
    public function canAccess()
    {
        return ($this->access_level == 'public_ro' || $this->canEdit()) ? true : false;
    }

    /*
     * Checks whether the current user is allowed to edit a given role according to their role level.
     *
     * @return boolean
     */
    public function canEdit()
    {
        if ($this->access_level == 'public_rw' || $this->role_level < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) {
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
	if ($this->role_level < auth()->user()->getRoleLevel() || $this->owned_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }
}
