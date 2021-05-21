<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Admin\ItemConfig;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

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
        $actions = $this->getActions('form');

        return view('admin.roles.form', compact('role', 'fields', 'actions'));
    }
}
