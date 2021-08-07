<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Models\Users\Role;
use App\Models\Users\Permission;
use App\Models\Users\User;

class RoleController extends Controller
{
    use ItemConfig, CheckInCheckOut;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'role';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'users';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.users.roles');
	$this->model = new Role;
    }

    /**
     * Show the role list.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Gather the needed data to build the item list.
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->model->getItems($request);
	$rows = $this->getRows($columns, $items);
	$this->setRowValues($rows, $columns, $items);

	$url = ['route' => 'admin.users.roles', 'item_name' => 'role', 'query' => $request->query()];
	$query = $request->query();

        return view('admin.users.roles.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new role.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(Request $request)
    {
        // Gather the needed data to build the form.
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$board = $this->getPermissionBoard();
	$query = $request->query();

        return view('admin.users.roles.form', compact('fields', 'actions', 'board', 'query'));
    }

    /**
     * Show the form for editing the specified role.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $role = Role::select('roles.*', 'users.name as owner_name', 'users2.name as modifier_name')
		      ->leftJoin('users', 'roles.created_by', '=', 'users.id')
		      ->leftJoin('users as users2', 'roles.updated_by', '=', 'users2.id')
		      ->findOrFail($id);

	if (!$role->canAccess()) {
	    return redirect()->route('admin.users.roles.index', $request->query())->with('error',  __('messages.generic.access_not_auth'));
	}

	if ($role->checked_out && $role->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.users.roles.index', $request->query())->with('error',  __('messages.generic.checked_out'));
	}

	// No need to check out the default roles as they can't be edited or deleted.
	if (!in_array($role->name, Role::getDefaultRoles())) {
	    $this->checkOut($role);
	}

        // Gather the needed data to build the form.

	// No need access level feature for the default roles.
	$except = (in_array($role->name, Role::getDefaultRoles())) ? ['_role_type', 'updated_at', 'updated_by', 'owner_name', 'access_level', 'created_by'] : [];

	if (empty($except)) {
	    $except = ($role->role_level > auth()->user()->getRoleLevel()) ? ['created_by'] : ['owner_name'];

	    if ($role->updated_by === null) {
		array_push($except, 'updated_by', 'updated_at');
	    }
	}

        $fields = $this->getFields($role, $except);
	$this->setFieldValues($fields, $role);
	$board = $this->getPermissionBoard($role);
	$except = (in_array($role->name, Role::getDefaultRoles())) ? ['save', 'saveClose', 'destroy'] : [];
        $actions = $this->getActions('form', $except);
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['role' => $id]);

        return view('admin.users.roles.form', compact('role', 'fields', 'actions', 'board', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  int  $id (optional)
     * @return Response
     */
    public function cancel(Request $request, $id = null)
    {
        if ($id) {
	    $record = Role::findOrFail($id);
	    $this->checkIn($record);
	}

	return redirect()->route('admin.users.roles.index', $request->query());
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

	if (in_array($role->id, Role::getDefaultRoleIds())) {
	    return redirect()->route('admin.users.roles.edit', $role->id)->with('error', __('messages.roles.cannot_update_default_roles'));
	}

	if (!$role->canEdit()) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('error',  __('messages.generic.edit_not_auth'));
	}

        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', Role::getDefaultRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('roles')->ignore($id)
	    ],
	]);

	$role->name = $request->input('name');
	$role->updated_by = auth()->user()->id;

	// Ensure the current user has a higher role level than the item owner's or the current user is the item owner.
	if (auth()->user()->getRoleLevel() > $role->role_level || $role->created_by == auth()->user()->id) {
	    $role->created_by = $request->input('created_by');
	    $owner = User::findOrFail($role->created_by);
	    $role->role_level = $owner->getRoleLevel();
	    $role->access_level = $request->input('access_level');
	}

	$role->save();

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any level1 permissions. 
	    $level1Perms = Permission::getPermissionNameList(['level2', 'level3']);
	    $count = array_intersect($request->input('permissions'), $level1Perms);

	    if (Role::getUserRoleType(auth()->user()) == 'admin' && $count) {
		return redirect()->route('admin.users.roles.edit', $role->id)->with('error', __('messages.roles.permission_not_auth'));
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

        if ($request->input('_close', null)) {
	    $this->checkIn($role);
	    return redirect()->route('admin.users.roles.index', $request->query())->with('success', __('messages.roles.update_success'));
	}

	return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('success', __('messages.roles.update_success'));
     
    }

    /**
     * Store a new role.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', Role::getDefaultRoles()).')$/i',
		'regex:/^[a-z0-9-]{3,}$/',
		'unique:roles'
	    ],
	]);

	$role = Role::create(['name' => $request->input('name')]);

	// Set the permission list.
	
        if ($request->input('permissions') !== null) {

	    // Ensure an admin doesn't use any level1 permissions. 
	    $level1Perms = Permission::getPermissionNameList(['level2', 'level3']);
	    $count = array_intersect($request->input('permissions'), $level1Perms);

	    if (Role::getUserRoleType(auth()->user()) == 'admin' && $count) {
		return redirect()->route('admin.users.roles.edit', $role->id)->with('error', __('messages.roles.permission_not_auth'));
	    }

	    foreach ($request->input('permissions') as $permission) {
		$role->givePermissionTo($permission);
	    }
	}

	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.roles.index', $query)->with('success', __('messages.roles.create_success'));
	}

	$query['role'] = $role->id;

	return redirect()->route('admin.users.roles.edit', $query)->with('success', __('messages.roles.create_success'));
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
	$role = Role::findOrFail($id);

	if (!$role->canDelete()) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	if (in_array($role->name, Role::getDefaultRoles())) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('error', __('messages.roles.cannot_delete_default_roles'));
	}

	if ($role->users->count()) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('error', __('messages.roles.users_assigned_to_roles', ['name' => $role->name]));
	}

	$name = $role->name;
	$role->delete();

	return redirect()->route('admin.users.roles.index', $request->query())->with('success', __('messages.roles.delete_success', ['name' => $name]));
    }

    /**
     * Remove one or more roles from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
	// Check for default roles.
        $roles = Role::whereIn('id', $request->input('ids'))->pluck('name')->toArray();
	$result = array_intersect($roles, Role::getDefaultRoles());

	if (!empty($result)) {
	    return redirect()->route('admin.users.roles.index', $request->query())->with('error',  __('messages.roles.cannot_delete_roles', ['roles' => implode(',', $result)]));
	}

	// Check for dependencies and permissions.
	foreach ($request->input('ids') as $id) {
	    $role = Role::findOrFail($id);

	    if ($role->users->count()) {
		return redirect()->route('admin.users.roles.index', $request->query())->with('error', __('messages.roles.users_assigned_to_roles', ['name' => $role->name]));
	    }

	    if (!$role->canDelete()) {
		return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('error',  __('messages.generic.delete_not_auth'));
	    }
	}

	Role::destroy($request->input('ids'));

	return redirect()->route('admin.users.roles.index', $request->query())->with('success', __('messages.roles.delete_list_success', ['number' => count($request->input('ids'))]));
    }

    /**
     * Checks in one or more roles.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = $this->checkInMultiple($request->input('ids'), '\\Spatie\\Permission\\Models\\Role');

	return redirect()->route('admin.users.roles.index', $request->query())->with($messages);
    }

    /*
     * Builds the permission board.
     */
    private function getPermissionBoard($role = null)
    {
        // N.B: Only super-admin and users type admin are allowed to manage roles.

        $userRoleType = Role::getUserRoleType(auth()->user());
	$hierarchy = Role::getRoleHierarchy();
	$isDefault = ($role && in_array($role->id, Role::getDefaultRoleIds())) ? true : false;

	if ($userRoleType == 'admin' && !$isDefault) {
	    // Restrict permissions for users type admin.
	    $permList = Permission::getPermissionList(['level1']);
	}
	// super-admin
	else {
	    $permList = Permission::getPermissionList();
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

                    $roleType = Role::defineRoleType($role);

		    if ($role->name == 'super-admin') {
		        // super-admin has all permissions.
			$checkbox->checked = true;
			$roleType = 'super-admin';
		    }

		    if ($hierarchy[$roleType] >= $hierarchy[$userRoleType] || in_array($role->name, Role::getDefaultRoles())) {
			$checkbox->disabled = true;
		    }
		}

		$list[$section][] = $checkbox;
	    }
	}

	return $list;
    }

    /*
     * Sets the row values specific to the Role model.
     *
     * @param  Array  $rows
     * @param  Array of stdClass Objects  $columns
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $roles
     * @return void
     */
    private function setRowValues(&$rows, $columns, $roles)
    {
        foreach ($roles as $key => $role) {
	    foreach ($columns as $column) {
	        if ($column->name == 'access_level' && in_array($role->id, Role::getDefaultRoleIds())) {
		    $rows[$key]->access_level = __('labels.generic.public_ro');
		}

	        if ($column->name == 'created_by' && in_array($role->id, Role::getDefaultRoleIds())) {
		    $rows[$key]->created_by = __('labels.generic.system');
		}
	    }
	}
    }

    /*
     * Sets field values specific to the Role model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Users\Role  $role
     * @return void
     */
    private function setFieldValues(&$fields, $role)
    {
        $defaultRole = (in_array($role->name, Role::getDefaultRoles())) ? true : false;

        foreach ($fields as $field) {
	    if ($defaultRole) {
	        // Disable all field.
	        $field->extra = ['disabled'];
	    }

	    if ($field->name == '_role_type') {
	        $value = ($role->name == 'super-admin') ? 'super-admin' : Role::defineRoleType($role);
	        $field->value = $value;
	    }
	}
    }
}
