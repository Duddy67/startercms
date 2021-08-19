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
use App\Http\Requests\Users\Role\StoreRequest;
use App\Http\Requests\Users\Role\UpdateRequest;


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

        $except = ['updated_by', 'owner_name'];

	if (auth()->user()->getRoleName() != 'super-admin') {
	    $except[] = 'created_by';
	}

        $fields = $this->getFields(null, $except);
        $actions = $this->getActions('form', ['destroy']);
	$board = $this->getPermissionBoard();
	$query = $request->query();
	$permissions = file_get_contents(app_path().'/Models/Users/permission/permissions.json', true);

        return view('admin.users.roles.form', compact('fields', 'actions', 'board', 'query', 'permissions'));
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
	$except = (in_array($role->name, Role::getDefaultRoles())) ? ['role_type', 'updated_at', 'updated_by', 'owner_name', 'access_level', 'created_by'] : [];

	if (empty($except)) {
	    // Only the super-admin is allowed to select users.
	    $except = (auth()->user()->getRoleName() != 'super-admin') ? ['created_by'] : ['owner_name'];

	    if ($role->updated_by === null) {
		array_push($except, 'updated_by', 'updated_at');
	    }
	}

        $fields = $this->getFields($role, $except);
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
     * @param  \App\Models\Users\Role $role (optional)
     * @return Response
     */
    public function cancel(Request $request, Role $role = null)
    {
        if ($role) {
	    $this->checkIn($role);
	}

	return redirect()->route('admin.users.roles.index', $request->query());
    }

    /**
     * Update the specified role.
     *
     * @param  \App\Http\Requests\Users\Role\UpdateRequest  $request
     * @param  \App\Models\Users\Role $role
     * @return Response
     */
    public function update(UpdateRequest $request, Role $role)
    {
	if (in_array($role->id, Role::getDefaultRoleIds())) {
	    return redirect()->route('admin.users.roles.edit', $role->id)->with('error', __('messages.roles.cannot_update_default_roles'));
	}

	if (!$role->canEdit()) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $role->id]))->with('error',  __('messages.generic.edit_not_auth'));
	}

	$role->name = $request->input('name');
	$role->updated_by = auth()->user()->id;

	// Ensure the current user has a higher role level than the item owner's or the current user is the item owner.
	if (auth()->user()->getRoleLevel() > $role->role_level || $role->created_by == auth()->user()->id) {

	    if (auth()->user()->getRoleName() == 'super-admin') {
		$role->created_by = $request->input('created_by');
	    }

	    $owner = User::findOrFail($role->created_by);
	    $role->role_level = $owner->getRoleLevel();
	    $role->access_level = $request->input('access_level');
	}

	$role->save();

	// Set the permission list.
	
	$permissions = Permission::getPermissionsWithoutSections();

	foreach ($permissions as $permission) {

	    $optional = (isset($permission->optional) && preg_match('#'.$role->role_type.'#', $permission->optional)) ? true : false;

	    // Check the optional permissions.
	    // Note: No need to check the default permissions since they have been set during the storing process and cannot be modified anymore.

	    if ($optional && in_array($permission->name, $request->input('permissions', [])) && !$role->hasPermissionTo($permission->name)) {
		  $role->givePermissionTo($permission->name);
	    }
	    elseif ($optional && !in_array($permission->name, $request->input('permissions', [])) && $role->hasPermissionTo($permission->name)) {
		 $role->revokePermissionTo($permission->name);
	    }
	}

        if ($request->input('_close', null)) {
	    $this->checkIn($role);
	    return redirect()->route('admin.users.roles.index', $request->query())->with('success', __('messages.roles.update_success'));
	}

	return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $role->id]))->with('success', __('messages.roles.update_success'));
     
    }

    /**
     * Store a new role.
     *
     * @param  \App\Http\Requests\Users\Role\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
	// Ensure first that an admin doesn't use any level1 permissions. 
	$level1Perms = Permission::getPermissionNameList(['level2', 'level3']);
	$count = array_intersect($request->input('permissions', []), $level1Perms);

	if (auth()->user()->getRoleType() == 'admin' && $count) {
	    return redirect()->route('admin.users.roles.edit', $role->id)->with('error', __('messages.roles.permission_not_auth'));
	}


	$permissions = Permission::getPermissionsWithoutSections();
	$toGiveTo = [];

	foreach ($permissions as $permission) {

	    $roles = (preg_match('#'.$request->input('role_type').'#', $permission->roles)) ? true : false;
	    $optional = (isset($permission->optional) && preg_match('#'.$request->input('role_type').'#', $permission->optional)) ? true : false;

	    if ($roles && !$optional) {
		 $toGiveTo[] = $permission;
	    }
	    elseif ($optional && in_array($permission->name, $request->input('permissions', []))) {
		 $toGiveTo[] = $permission;
	    }
	}

	$role = Role::create([
	    'name' => $request->input('name'),
	    'access_level' => $request->input('access_level'),
	    'created_by' => $request->input('created_by', auth()->user()->id)
	]);

        foreach ($toGiveTo as $permission) {
	    $role->givePermissionTo($permission->name);
	}

	$role->role_type = $request->input('role_type');
	$role->save();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.roles.index', $request->query())->with('success', __('messages.roles.create_success'));
	}

	return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $role->id]))->with('success', __('messages.roles.create_success'));
    }

    /**
     * Remove the specified role from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Users\Role $role
     * @return Response
     */
    public function destroy(Request $request, Role $role)
    {
	if (!$role->canDelete()) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $role->id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	if (in_array($role->name, Role::getDefaultRoles())) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $role->id]))->with('error', __('messages.roles.cannot_delete_default_roles'));
	}

	if ($role->users->count()) {
	    return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $role->id]))->with('error', __('messages.roles.users_assigned_to_roles', ['name' => $role->name]));
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
	// Check first for default roles.
        $roles = Role::whereIn('id', $request->input('ids'))->pluck('name')->toArray();
	$result = array_intersect($roles, Role::getDefaultRoles());

	if (!empty($result)) {
	    return redirect()->route('admin.users.roles.index', $request->query())->with('error',  __('messages.roles.cannot_delete_roles', ['roles' => implode(',', $result)]));
	}

	$roles = [];

	// Then check for dependencies and permissions.
	foreach ($request->input('ids') as $id) {
	    $role = Role::findOrFail($id);

	    if ($role->users->count()) {
	        // Some users are already assigned to this role.
		return redirect()->route('admin.users.roles.index', $request->query())->with('error', __('messages.roles.users_assigned_to_roles', ['name' => $role->name]));
	    }

	    if (!$role->canDelete()) {
		return redirect()->route('admin.users.roles.edit', array_merge($request->query(), ['role' => $id]))->with('error',  __('messages.generic.delete_not_auth'));
	    }

	    $roles[] = $role;
	}

	foreach ($roles as $role) {
	    $role->delete();
	}

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

        $userRoleType = auth()->user()->getRoleType();
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
		$checkbox->dataset = ['data-section' => $section];
		$checkbox->checked = false;

		if ($role) {
		    try {
			if ($role->hasPermissionTo($permission->name)) {
			    $checkbox->checked = true;
			}

			$optional = (isset($permission->optional)) ? explode('|', $permission->optional) : [];
			$checkbox->disabled = (in_array($role->role_type, $optional)) ? false : true;
		    }
		    catch (\Exception $e) {
			$checkbox->label = $permission->name.' (missing !)';
			$checkbox->disabled = true;
			$list[$section][] = $checkbox;

		        continue;
		    }

		    // Disable permissions according to the edited role type.

		    if ($role->name == 'super-admin') {
		        // super-admin has all permissions.
			$checkbox->checked = true;
			$role->role_type = 'super-admin';
		    }

		    if ($hierarchy[$role->role_type] >= $hierarchy[$userRoleType] || in_array($role->name, Role::getDefaultRoles())) {
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
        // Code 
    }
}
