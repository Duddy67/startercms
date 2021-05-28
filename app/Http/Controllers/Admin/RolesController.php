<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Models\Settings;
use App\Models\User;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    use ItemConfig, HasRoles;

    public $reservedRoles;
    public $reservedRoleIds;
    public $privatePerms;
    public $protectedPerms;

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
	$this->reservedRoles = Settings::getReservedRoles();
	$this->reservedRoleIds = Settings::getReservedRoleIds();
	$this->privatePerms = Settings::getPrivatePermissions();
	$this->protectedPerms = Settings::getProtectedPermissions();
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
	//$this->setRowValues($rows, $columns, $roles);

        return view('admin.roles.list', compact('roles', 'columns', 'rows', 'actions'));
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

	if (in_array($role->id, $this->reservedRoleIds)) {
	    return redirect()->route('admin.roles.edit', $role->id)->with('error', 'This role is reserved.');
	}

        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^\s*('.implode('|', $this->reservedRoles).')\s*$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('roles')->ignore($id)
	    ],
	]);

	$role->name = $request->input('name');
	$role->save();

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any private permissions. 
	    $count = array_intersect($request->input('permissions'), $this->privatePerms);

	    if (User::getRoleType() == 'admin' && $count) {
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
		'not_regex:/^\s*('.implode('|', $this->reservedRoles).')\s*$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		'unique:roles'
	    ],
	]);

	$role = Role::create(['name' => $request->input('name')]);

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any private permissions. 
	    $count = array_intersect($request->input('permissions'), $this->privatePerms);

	    if (User::getRoleType() == 'admin' && $count) {
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

	if (in_array($role->name, $this->reservedRoles)) {
	    return redirect()->route('admin.roles.edit', $role->id)->with('error', 'This role is reserved.');
	}

	//$role->delete();

	return redirect()->route('admin.roles.index')->with('success', 'Role successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
        $roles = Role::whereIn('id', $request->input('ids'))->pluck('name')->toArray();
	$result = array_intersect($roles, $this->reservedRoles);

	if (!empty($result)) {
	    return redirect()->route('admin.roles.index')->with('error', 'The following roles are reserved: '.implode(',', $result));
	}

	//Role::destroy($request->input('ids'));

	return redirect()->route('admin.roles.index')->with('success', count($request->input('ids')).' Role(s) successfully deleted.');
    }

    private function getPermissionList($role = null)
    {
        $userRoleType = User::getRoleType();

	if (($role === null || !in_array($role->name, $this->reservedRoles)) && $userRoleType == 'admin') {
	    // Restrict permissions for admins.
	    $permissions = Permission::whereNotIn('name', $this->privatePerms)->get();
	}
	// super-admin
	else {
	    $permissions = Permission::all();
	}

	$list = [];

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

		// Disable permissions according to the edited default role type.

		if ($role->name == 'super-admin') {
		    $checkbox->checked = true;
		    $checkbox->disabled = true;
		}

		if ($role->name == 'admin' && (in_array($permission->name, $this->privatePerms) || $permission->name == 'create-user')) {
		    $checkbox->disabled = true;
		}

		if ($role->name == 'manager' && (in_array($permission->name, $this->privatePerms) || in_array($permission->name, $this->protectedPerms) || $permission->name == 'create-post')) {
		    $checkbox->disabled = true;
		}

		if ($role->name == 'assistant' &&
		    (in_array($permission->name, $this->privatePerms) || in_array($permission->name, ['access-admin', 'create-user', 'update-own-user', 'delete-own-user']))) {
		    $checkbox->disabled = true;
		}
	    }

	    $list[] = $checkbox;
	}

	return $list;
    }
}
