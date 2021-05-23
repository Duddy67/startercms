<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Admin\ItemConfig;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesController extends Controller
{
    use ItemConfig, HasRoles;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
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
	//$this->setRowValues($rows, $columns, $roles);

        return view('admin.roles.list', compact('roles', 'columns', 'rows', 'actions'));
    }

    public function create()
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');

        return view('admin.roles.form', compact('fields', 'actions'));
    }

    public function edit($id)
    {
        $role = Role::findById($id);
        $fields = $this->getFields($role);
	$board = $this->getPermissionBoard($role);
        $actions = $this->getActions('form');

        return view('admin.roles.form', compact('role', 'fields', 'actions', 'board'));
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

        if ($request->input('permissions') !== null) {
	    foreach ($request->input('permissions') as $permission) {
	        $role->givePermissionTo($permission);
	    }
	}
file_put_contents('debog_file.txt', print_r($request->all(), true));
     
    }

    private function getPermissionBoard($role)
    {
        $permissions = Permission::all();
	$board = [];

	foreach ($permissions as $permission) {
	    $checkbox = new \stdClass();
	    $checkbox->type = 'checkbox';
	    $checkbox->label = $permission->name;
	    $checkbox->id = $permission->name;
	    $checkbox->name = 'permissions[]';
	    $checkbox->value = $permission->name;
	    $checkbox->checked = false;

	    if ($role->hasPermissionTo($permission->name)) {
	        $checkbox->checked = true;
	    }

	    $board[] = $checkbox;
	}

	return $board;
    }
}
