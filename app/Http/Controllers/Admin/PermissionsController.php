<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\RolesPermissions;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Permission;

class PermissionsController extends Controller
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

        return view('admin.permissions.list', compact('columns', 'rows', 'actions'));
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

	if (in_array($permission->name, $this->getReservedPermissions())) {
	    return redirect()->route('admin.permissions.index')->with('error', 'The permission "'.$permission->name.'" is reserved.');
	}

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
	$permission = Permission::findOrFail($id);

        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', $this->getReservedPermissions()).')$/i',
		'regex:/^'.implode('|', $this->getPermissionPatterns()).'$/',
		Rule::unique('permissions')->ignore($id)
	    ],
	],
	// Custom messages.
	[
	    'name.required' => 'This field is required.',
	    'name.not_regex' => 'This permission is reserved.',
	    'name.regex' => 'The permission name is invalid.',
	    'name.unique' => 'The permission is already taken.',
	]);

	$permission->name = $request->input('name');
	$permission->save();

	$message = 'Permission successfully updated.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.permissions.index')->with('success', $message);
	}

	return redirect()->route('admin.permissions.edit', $permission->id)->with('success', $message);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => [
		'required',
		'not_regex:/^('.implode('|', $this->getReservedPermissions()).')$/i',
		'regex:/^'.implode('|', $this->getPermissionPatterns()).'$/',
		'unique:permissions'
	    ],
	],
	// Custom messages.
	[
	    'name.required' => 'This field is required.',
	    'name.not_regex' => 'This permission is reserved.',
	    'name.regex' => 'The permission name is invalid.',
	    'name.unique' => 'The permission is already taken.',
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
	$permission = Permission::findOrFail($id);

	if (in_array($permission->id, $this->getReservedPermissionIds())) {
	    return redirect()->route('admin.permissions.edit', $permission->id)->with('error', 'This permission is reserved.');
	}

	$permission->delete();

	return redirect()->route('admin.permissions.index')->with('success', 'Permission successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
        $permissions = Permission::whereIn('id', $request->input('ids'))->pluck('name')->toArray();
	$result = array_intersect($permissions, $this->getReservedPermissions());

	if (!empty($result)) {
	    return redirect()->route('admin.permissions.index')->with('error', 'The following permissions are reserved: '.implode(',', $result));
	}

	Permission::destroy($request->input('ids'));

	return redirect()->route('admin.permissions.index')->with('success', count($request->input('ids')).' Permission(s) successfully deleted.');
    }
}
