<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\RolesPermissions;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    use ItemConfig, RolesPermissions;

    /*
     * Name of the model.
     */
    protected $modelName = 'role';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.users.roles');
    }

    /**
     * Show the role list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->getItems($request);
	$rows = $this->getRows($columns, $items);
	$url = ['route' => 'admin.users.roles', 'item_name' => 'role', 'query' => $request->query()];
	$query = $request->query();

        return view('admin.users.roles.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    public function create(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$board = $this->getPermissionBoard();
	$query = $request->query();

        return view('admin.users.roles.form', compact('fields', 'actions', 'board', 'query'));
    }

    public function edit(Request $request, $id)
    {
        $role = Role::findById($id);
	$except = (in_array($role->name, $this->getDefaultRoles())) ? ['_role_type'] : [];
        $fields = $this->getFields($role, $except);
	$this->setFieldValues($fields, $role);
	$board = $this->getPermissionBoard($role);
        $actions = $this->getActions('form');
	$query = $queryWithId = $request->query();
	$queryWithId['role'] = $id;

        return view('admin.users.roles.form', compact('role', 'fields', 'actions', 'board', 'query', 'queryWithId'));
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

	if (in_array($role->id, $this->getDefaultRoleIds())) {
	    return redirect()->route('admin.users.roles.edit', $role->id)->with('error', 'You cannot modify the default roles.');
	}

        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', $this->getDefaultRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('roles')->ignore($id)
	    ],
	]);

	$role->name = $request->input('name');
	$role->save();

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any level1 permissions. 
	    $level1Perms = $this->getPermissionArray(['level2', 'level3']);
	    $count = array_intersect($request->input('permissions'), $level1Perms);

	    if ($this->getUserRoleType() == 'admin' && $count) {
		return redirect()->route('admin.users.roles.edit', $role->id)->with('error', 'One or more selected permissions are not authorised.');
	    }

	    // Get the unselected permissions.
	    $permissions = Permission::whereNotIn('name', $request->input('permissions'))->pluck('name')->toArray();

	    // Give the selected permissions.
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

	// Revoke the unselected permissions.
	foreach ($permissions as $permission) {
	    if ($role->hasPermissionTo($permission)) {
		$role->revokePermissionTo($permission);
	    }
	}

	$message = 'Permission successfully updated.';
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.roles.index', $query)->with('success', $message);
	}

	$query['role'] = $role->id;

	return redirect()->route('admin.users.roles.edit', $query)->with('success', $message);
     
    }

    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', $this->getDefaultRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		'unique:roles'
	    ],
	]);

	$role = Role::create(['name' => $request->input('name')]);

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any level1 permissions. 
	    $level1Perms = $this->getPermissionArray(['level2', 'level3']);
	    $count = array_intersect($request->input('permissions'), $level1Perms);

	    if ($this->getUserRoleType() == 'admin' && $count) {
		return redirect()->route('admin.users.roles.edit', $role->id)->with('error', 'One or more selected permissions are not authorised.');
	    }

	    foreach ($request->input('permissions') as $permission) {
		$role->givePermissionTo($permission);
	    }
	}

	$message = 'Role successfully added.';
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.roles.index', $query)->with('success', $message);
	}

	$query['role'] = $role->id;

	return redirect()->route('admin.users.roles.edit', $query)->with('success', $message);
    }

    public function destroy(Request $request, $id)
    {
	$role = Role::findOrFail($id);
	$query = $request->query();

	if (in_array($role->name, $this->getDefaultRoles())) {
	    $query['role'] = $role->id;
	    return redirect()->route('admin.users.roles.edit', $query)->with('error', 'You cannot delete the default roles.');
	}

	if ($role->users->count()) {
	    $query['role'] = $role->id;
	    return redirect()->route('admin.users.roles.edit', $query)->with('error', 'One or more users are assigned to this role.');
	}

	$role->delete();

	return redirect()->route('admin.users.roles.index', $query)->with('success', 'Role successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
        $roles = Role::whereIn('id', $request->input('ids'))->pluck('name')->toArray();
	$result = array_intersect($roles, $this->getDefaultRoles());

	if (!empty($result)) {
	    return redirect()->route('admin.users.roles.index', $request->query())->with('error', 'The following roles cannot be deleted: '.implode(',', $result));
	}

	foreach ($request->input('ids') as $id) {
	    $role = Role::findOrFail($id);

	    if ($role->users->count()) {
		return redirect()->route('admin.users.roles.index', $request->query())->with('error', 'One or more users are assigned to this role: '.$role->name);
	    }
	}

	Role::destroy($request->input('ids'));

	return redirect()->route('admin.users.roles.index', $request->query())->with('success', count($request->input('ids')).' Role(s) successfully deleted.');
    }

    private function getPermissionBoard($role = null)
    {
        // N.B: Only super-admin and users type admin are allowed to manage roles.

        $userRoleType = $this->getUserRoleType();
	$hierarchy = $this->getRoleHierarchy();

	if ($userRoleType == 'admin') {
	    // Restrict permissions for users type admin.
	    $permList = $this->getPermissionList(['level1']);
	}
	// super-admin
	else {
	    $permList = $this->getPermissionList();
	}

	$list = [];

	foreach ($permList as $section => $permissions) {
	    $list[$section] = [];

	    foreach ($permissions as $permission) {
		$checkbox = new \stdClass();
		$checkbox->type = 'checkbox';
		$checkbox->label = $permission->name;
		$checkbox->position = 'right';
		$checkbox->id = $permission->name;
		$checkbox->name = 'permissions[]';
		$checkbox->value = $permission->name;
		$checkbox->checked = false;

		if ($role) {
		    try {
			if ($role->hasPermissionTo($permission->name)) {
			    $checkbox->checked = true;
			}
		    }
		    catch (\Exception $e) {
			$checkbox->label = $permission->name.' (missing !)';
			$checkbox->disabled = true;
			$list[$section][] = $checkbox;

		        continue;
		    }

		    // Disable permissions according to the edited role type.

                    $roleType = $this->getRoleType($role);

		    if ($role->name == 'super-admin') {
		        // super-admin has all permissions.
			$checkbox->checked = true;
			$roleType = 'super-admin';
		    }

		    if ($hierarchy[$roleType] >= $hierarchy[$userRoleType] || in_array($role->name, $this->getDefaultRoles())) {
			$checkbox->disabled = true;
		    }
		}

		$list[$section][] = $checkbox;
	    }
	}

	return $list;
    }

    /*
     * Sets field values specific to the Role model.
     */
    private function setFieldValues(&$fields, $role)
    {
        $defaultRole = (in_array($role->name, $this->getDefaultRoles())) ? true : false;

        foreach ($fields as $field) {
	    if ($defaultRole) {
	        // Disable all field.
	        $field->extra = ['disabled'];
	    }

	    if ($field->name == '_role_type') {
	        $value = ($role->name == 'super-admin') ? 'super-admin' : $this->getRoleType($role);
	        $field->value = $value;
	    }
	}
    }
}
