<?php

namespace App\Traits\Admin;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

trait RolesPermissions
{
    /*
     * Roles that cannot be deleted nor updated.
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

    public function getDefaultRoleIds()
    {
        return [1,2,3,4,5];
    }

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

    public function getPermissionList($except = [])
    {
	$json = file_get_contents(app_path().'/Models/permission/permissions.json', true);

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

    public function getPermissionArray($except = [])
    {
        $list = $this->getPermissionList($except);
	$array = [];

	foreach ($list as $permissions) {
	    foreach ($permissions as $permission) {
	        $array[] = $permission->name;
	    }
	}

	return $array;
    }

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

    public function getAssignableRoles($user = null)
    {
        $roleType = $this->getUserRoleType();

	// Check first if the user is editing their own user account.
	if ($user && auth()->user()->id == $user->id) {
	    // Only display the user role.
	    $roles = Role::where('name', $user->getRoleNames()->toArray()[0])->get();
	}
	// Move on to the role types.
	elseif ($roleType == 'registered') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->whereNotIn('name', ['create-user', 'create-permission', 'create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	elseif ($roleType == 'manager') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->where('name', 'create-user')->whereNotIn('name', ['create-permission', 'create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	elseif ($roleType == 'admin') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->whereIn('name', ['create-permission', 'create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	// super-admin
	else {
	    $roles = Role::whereNotIn('name', ['super-admin'])->get();
	}

	return $roles;
    }

    public function buildPermissions($request, $rebuild = false)
    {
        if (!auth()->user()->isAllowedTo('update-permissions')) {
	    $request->session()->flash('error', 'You are not allowed to update permissions.');

	    return;
	}

	if ($rebuild) {
	    $this->truncatePermissions();
	}

	$permissions = $this->getPermissionArray();
	$invalidNames = [];
	$count = 0;

	foreach ($permissions as $permission) {
	  if (Permission::where('name', $permission)->first() === null) {
	      if (!preg_match('#^'.implode('|', $this->getPermissionPatterns()).'$#', $permission)) {
		  $invalidNames[] = $permission;
		  continue;
	      }

	      Permission::create(['name' => $permission]);

	      $count++;
	  }
	}

	if (!empty($invalidNames)) {
	    $request->session()->flash('error', 'The permission names: "'.implode(', ', $invalidNames).'" are invalid.');
	}

	if ($count) {
	    $request->session()->flash('success', $count.' permission(s) successfully updated.');
	}
	else {
	    $request->session()->flash('info', 'No new permissions added.');
	}

	if ($rebuild && empty($invalidNames)) {
	    $this->setPermissions();
	    $request->session()->flash('success', 'Permissions have been successfully rebuilt.');
	}
    }

    public function setPermissions()
    {
	$permList = $this->getPermissionList();

	foreach ($permList as $permissions) {
	    foreach ($permissions as $permission) {
	        $roles = explode('|', $permission->default);

		foreach ($roles as $role) {
		    if (!empty($role)) {
			$role = Role::findByName($role);
			$role->givePermissionTo($permission->name);
		    }
		}
	    }
	}
    }

    public function truncatePermissions()
    {
	Schema::disableForeignKeyConstraints();
	DB::table('permissions')->truncate();
	DB::table('role_has_permissions')->truncate();
	Schema::enableForeignKeyConstraints();

	Artisan::call('cache:clear');
    }

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
