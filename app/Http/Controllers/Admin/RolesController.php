<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\RolesPermissions;
use App\Models\User;
use App\Models\Settings;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    use ItemConfig, RolesPermissions, HasRoles;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.roles');
	$this->itemName = 'role';
    }

    /**
     * Show the role list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $roles = Role::all();
	$rows = $this->getRows($columns, $roles);

        return view('admin.roles.list', compact('columns', 'rows', 'actions'));
    }

    public function create()
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$list = $this->getPermissionList();

        return view('admin.roles.form', compact('fields', 'actions', 'list'));
    }

    public function edit($id)
    {
        $role = Role::findById($id);
        $fields = $this->getFields($role);
	$list = $this->getPermissionList($role);
        $actions = $this->getActions('form');

        return view('admin.roles.form', compact('role', 'fields', 'actions', 'list'));
    }

    /**
     * Update the specified role.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
	$role = Role::findOrFail($id);
//file_put_contents('debog_file.txt', print_r($arr, true));

	if (in_array($role->id, $this->getReservedRoleIds())) {
	    return redirect()->route('admin.roles.edit', $role->id)->with('error', 'This role is reserved.');
	}

        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', $this->getReservedRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('roles')->ignore($id)
	    ],
	]);

	$role->name = $request->input('name');
	$role->save();

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any level1 permissions. 
	    $level1Perms = Settings::getPermissionArray(['level2', 'level3']);
	    $count = array_intersect($request->input('permissions'), $level1Perms);

	    if (User::getUserRoleType() == 'admin' && $count) {
		return redirect()->route('admin.roles.edit', $role->id)->with('error', 'One or more selected permissions are not authorised.');
	    }

	    // Get the unselected permissions.
	    $permissions = Permission::whereNotIn('name', $request->input('permissions'))->pluck('name')->toArray();

	    foreach ($request->input('permissions') as $permission) {
	        if (!$role->hasPermissionTo($permission)) {
		    $role->givePermissionTo($permission);
		}
	    }
	}
	else {
	    // Get all of the permissions.
	    $permissions = Permission::all()->pluck('name')->toArray();
	}

	foreach ($permissions as $permission) {
	    if ($role->hasPermissionTo($permission)) {
		$role->revokePermissionTo($permission);
	    }
	}

	$message = 'Permission successfully updated.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.roles.index')->with('success', $message);
	}

	return redirect()->route('admin.roles.edit', $role->id)->with('success', $message);
     
    }

    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', $this->getReservedRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		'unique:roles'
	    ],
	]);

	$role = Role::create(['name' => $request->input('name')]);

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any level1 permissions. 
	    $level1Perms = Settings::getPermissionArray(['level2', 'level3']);
	    $count = array_intersect($request->input('permissions'), $level1Perms);

	    if (User::getUserRoleType() == 'admin' && $count) {
		return redirect()->route('admin.roles.edit', $role->id)->with('error', 'One or more selected permissions are not authorised.');
	    }

	    foreach ($request->input('permissions') as $permission) {
		$role->givePermissionTo($permission);
	    }
	}

	$message = 'Role successfully added.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.roles.index')->with('success', $message);
	}

	return redirect()->route('admin.roles.edit', $role->id)->with('success', $message);
    }

    public function destroy($id)
    {
	$role = Role::findOrFail($id);

	if (in_array($role->name, $this->getReservedRoles())) {
	    return redirect()->route('admin.roles.edit', $role->id)->with('error', 'This role is reserved.');
	}

	if ($role->users->count()) {
	    return redirect()->route('admin.roles.edit', $role->id)->with('error', 'One or more users are assigned to this role.');
	}

	$role->delete();

	return redirect()->route('admin.roles.index')->with('success', 'Role successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
        $roles = Role::whereIn('id', $request->input('ids'))->pluck('name')->toArray();
	$result = array_intersect($roles, $this->getReservedRoles());

	if (!empty($result)) {
	    return redirect()->route('admin.roles.index')->with('error', 'The following roles are reserved: '.implode(',', $result));
	}

	foreach ($request->input('ids') as $id) {
	    $role = Role::findOrFail($id);

	    if ($role->users->count()) {
		return redirect()->route('admin.roles.index')->with('error', 'One or more users are assigned to this role: '.$role->name);
	    }
	}

	Role::destroy($request->input('ids'));

	return redirect()->route('admin.roles.index')->with('success', count($request->input('ids')).' Role(s) successfully deleted.');
    }

    private function getPermissionList($role = null)
    {
        // N.B: Only super-admin and users type admin are allowed to manage roles.

        $userRoleType = User::getUserRoleType();
	$hierarchy = User::getRoleHierarchy();

	if ($userRoleType == 'admin') {
	    // Restrict permissions for users type admin.
	    $permList = Settings::getPermissionList(['level1']);
	}
	// super-admin
	else {
	    $permList = Settings::getPermissionList();
	}

	$list = [];

	foreach ($permList as $section => $permissions) {
	    $list[$section] = [];

	    foreach ($permissions as $permission) {
		$checkbox = new \stdClass();
		$checkbox->type = 'checkbox';
		$checkbox->label = $permission->name;
		$checkbox->id = $permission->name;
		$checkbox->name = 'permissions[]';
		$checkbox->value = $permission->name;
		$checkbox->checked = false;

		if ($role) {
		    if ($role->hasPermissionTo($permission->name)) {
			$checkbox->checked = true;
		    }

		    // Disable permissions according to the edited role type.

                    $roleType = User::getRoleType($role);

		    if ($role->name == 'super-admin') {
		        // super-admin has all permissions.
			$checkbox->checked = true;
			$roleType = 'super-admin';
		    }

		    if ($hierarchy[$roleType] >= $hierarchy[$userRoleType]) {
			$checkbox->disabled = true;
		    }
		}

		$list[$section][] = $checkbox;
	    }
	}

	return $list;
    }
}
