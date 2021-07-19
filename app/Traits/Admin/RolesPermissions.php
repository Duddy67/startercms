<?php

namespace App\Traits\Admin;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use App\Models\Settings\General;


trait RolesPermissions
{
    /*
     * Roles that cannot be deleted nor updated.
     *
     * @return Array
     */
    public function getDefaultRoles()
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
    public function getDefaultRoleIds()
    {
        return [1,2,3,4,5];
    }

    /*
     * Role types which are allowed to create users and modify their roles.
     *
     * @return Array
     */
    public function getAllowedRoleTypes()
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
    public function getRoleHierarchy()
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
     * Validation patterns for permission names.
     *
     * @return Array
     */
    public function getPermissionPatterns()
    {
        return [
	    'create-[0-9-a-z\-]+',
	    'update-[0-9-a-z\-]+',
	    'delete-[0-9-a-z\-]+',
	    'update-own-[0-9-a-z\-]+',
	    'delete-own-[0-9-a-z\-]+',
	    '[0-9-a-z\-]+-settings',
	    'access-admin'
	];
    }

    /*
     * Gets the permissions.json file and returns it as a list.
     *
     * @param Array  $except (optional)
     * @return Array of stdClass Objects.
     */
    public function getPermissionList($except = [])
    {
	$json = file_get_contents(app_path().'/Models/Users/permission/permissions.json', true);

        if ($json === false) {
	   throw new Exception('Load Failed');    
	}

	$list = json_decode($json);

	if (!empty($except)) {
	    foreach ($list as $section => $permissions) {
		foreach ($permissions as $key => $permission) {
		    if (in_array($permission->type, $except)) {
		        unset($list->$section[$key]);
		    }

		    // Remove empty sections.
		    if (empty($list->$section)) {
		        unset($list->$section);
		    }
		}
	    }
	}

	return $list;
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

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

    /*
     * Returns the permission names.
     *
     * @param Array  $except (optional)
     * @return Array
     */
    public function getPermissionNameList($except = [])
    {
        $list = $this->getPermissionList($except);
	$nameList = [];

	foreach ($list as $permissions) {
	    foreach ($permissions as $permission) {
	        $nameList[] = $permission->name;
	    }
	}

	return $nameList;
    }

    /*
     * Returns the role type of a given user or of the current user.
     *
     * @param \App\Models\Users\User  $user (optional)
     * @return string
     */
    public function getUserRoleType($user = null)
    {
        // Get the given user or the current user.
        $user = ($user) ? $user : auth()->user();
        $roleName = $user->getRoleNames()->toArray()[0];

	if ($roleName == 'super-admin') {
	    return 'super-admin';
	}

	return $this->getRoleType($roleName);

    }

    /*
     * Returns the type of a given role according its permissions.
     *
     * @param \Spatie\Permission\Models\Role or string  $role
     * @return string
     */
    public function getRoleType($role)
    {
	$role = (is_string($role)) ? Role::findByName($role) : $role;

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

    public function getUserRoleLevel($user = null)
    {
        $roleType = $this->getUserRoleType($user);
	return $this->getRoleHierarchy()[$roleType];
    }

    /*
     * Returns the roles that a user is allowed to assign to an other user.
     *
     * @param  \App\Models\Users\User $user (optional)
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignableRoles($user = null)
    {
	// Check first if the user is editing their own user account.
	if ($user && auth()->user()->id == $user->id) {
	    // Only display the user's role as users cannot change their own role.
	    return Role::where('name', $user->getRoleNames()->toArray()[0])->get();
	}

	// Get the current user's role type.
        $roleType = $this->getUserRoleType();

	if (!in_array($roleType, $this->getAllowedRoleTypes())) {
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
     * Returns the users that a user is allowed to assign as owner of an item.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getAssignableUsers()
    {
	$results = $this->getAssignableRoles();

	if ($results->isEmpty()) {
	    return $results;
	}

        $roles = [];

	foreach ($results as $role) {
	    $roles[] = $role->name;
	}

	return \App\Models\Users\User::whereHas('roles', function ($query) use($roles) {
	    $query->whereIn('name', $roles);
	})->orWhere('id', auth()->user()->id)->get();
    }

    /*
     * Builds or rebuilds the permissions from the permissions.json file. 
     *
     * @param  Request  $request
     * @param  boolean  $rebuild  (optional)
     * @return void
     */
    public function buildPermissions($request, $rebuild = false)
    {
        // Only the super-admin is allowed to perform these tasks.
        if (!auth()->user()->hasRole('super-admin')) {
	    $request->session()->flash('error', 'You are not allowed to update or rebuild permissions.');

	    return;
	}

	if ($rebuild) {
	    $this->truncatePermissions();
	}

	$permissions = $this->getPermissionNameList();
	$invalidNames = [];
	$count = 0;

	foreach ($permissions as $permission) {
	  // Creates the new permissions.
	  if (Permission::where('name', $permission)->first() === null) {
	      // Check for permission names.
	      if (!preg_match('#^'.implode('|', $this->getPermissionPatterns()).'$#', $permission)) {
		  $invalidNames[] = $permission;
		  continue;
	      }

	      Permission::create(['name' => $permission]);

	      $count++;
	  }
	}

	if (!empty($invalidNames)) {
	    $request->session()->flash('error', __('messages.permissions.invalid_permission_names', ['names' => implode(', ', $invalidNames)]));
	    return;
	}

	if ($rebuild) {
	    if ($this->setPermissions($request)) {
		$request->session()->flash('success', __('messages.permissions.rebuild_success', ['number' => $count]));
	    }

	    return;
	}

	if ($count) {
	    $request->session()->flash('success', __('messages.permissions.build_success', ['number' => $count]));
	}
	else {
	    $request->session()->flash('info', __('messages.permissions.no_new_permissions'));
	}
    }

    /*
     * Sets the default roles' permissions.
     *
     * @param  Request  $request
     * @return void
     */
    private function setPermissions($request)
    {
	$permList = $this->getPermissionList();

	foreach ($permList as $permissions) {
	    foreach ($permissions as $permission) {
	        $roles = explode('|', $permission->default);

		foreach ($roles as $role) {
		    if (!empty($role)) {
		        try {
			    $role = Role::findByName($role);
			    $role->givePermissionTo($permission->name);
			}
			catch (\Exception $e) {
			    $request->session()->flash('error', __('messages.permissions.role_does_not_exist', ['name' => $role]));
			    return false;
			}
		    }
		}
	    }
	}

	return true;
    }

    /*
     * Empties the permissions and role permission pivot tables.
     *
     * @return void
     */
    private function truncatePermissions()
    {
	Schema::disableForeignKeyConstraints();
	DB::table('permissions')->truncate();
	DB::table('role_has_permissions')->truncate();
	Schema::enableForeignKeyConstraints();

	Artisan::call('cache:clear');
    }

    /*
     * Used during the very first registration (the super-user) in the CMS.
     *
     * @return void
     */
    public function createRoles()
    {
        if (Role::whereIn('name', $this->getDefaultRoles())->doesntExist()) {
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
}
