<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Traits\Admin\ItemConfig;
use App\Models\Settings;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;

class PermissionsController extends Controller
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
	$this->itemName = 'permission';
    }

    /**
     * Show the permission list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $permissions = Permission::all();
	$rows = $this->getRows($columns, $permissions);
	//$this->setRowValues($rows, $columns, $permissions);

        return view('admin.permissions.list', compact('permissions', 'columns', 'rows', 'actions'));
    }

    public function create()
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form', ['destroy']);

        return view('admin.permissions.form', compact('fields', 'actions'));
    }

    public function edit($id)
    {
        $permission = Permission::findById($id);
        $fields = $this->getFields($permission);
        $actions = $this->getActions('form');

        return view('admin.permissions.form', compact('permission', 'fields', 'actions'));
    }

    /**
     * Update the specified permission.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
	    'name' => 'bail|required|between:5,25|regex:/^[\pL\s\-]+$/u',
	    'email' => 'bail|required|email|unique:users,email',
	    'password' => 'nullable|confirmed|min:8'
	]);

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.permissions.index');
	}

        return var_dump($request->all());
    }

    public function store(Request $request)
    {
        $reservedPerms = Settings::getReservedPermissions();
        $permPatterns = Settings::getPermissionPatterns();
//file_put_contents('debog_file.txt', print_r($reservedPerms, true));

        $this->validate($request, [
	    'name' => [
		'required',
		//'not_regex:/'.implode('|', $reservedPerms).'/i',
		'regex:/^'.implode('|', $permPatterns).'$/',
		'unique:permissions'
	    ],
	]);

	$permission = Permission::create(['name' => $request->input('name')]);
	$message = 'Permission successfully added.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.permissions.index')->with('success', $message);
	}

	return redirect()->route('admin.permissions.edit', $permission->id)->with('success', $message);
    }

    public function destroy($id)
    {
	return redirect()->route('admin.permissions.index');
        return 'destroy';
    }

    public function massDestroy(Request $request)
    {
	return redirect()->route('admin.permissions.index');
    }

}
