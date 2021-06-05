<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\RolesPermissions;
use Spatie\Permission\Models\Permission;
use App\Models\Settings;

class PermissionsController extends Controller
{
    use ItemConfig, RolesPermissions;

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
        $list = $this->getPermissionBoard();

        return view('admin.permissions.list', compact('columns', 'list', 'actions'));
    }

    public function build(Request $request)
    {
	$this->buildPermissions($request);
	return redirect()->route('admin.permissions.index');
    }

    public function rebuild(Request $request)
    {
	$this->buildPermissions($request, true);
	return redirect()->route('admin.permissions.index');
    }

    private function getPermissionBoard()
    {
	$permList = $this->getPermissionList();

	$list = [];

	foreach ($permList as $section => $permissions) {
	    $list[$section] = [];

	    foreach ($permissions as $permission) {
	        $missing = '';
		if (Permission::where('name', $permission->name)->first() === null) {
		    $missing = ' (missing !)';
		}

		$list[$section][] = $permission->name.$missing;
	    }
	}

	return $list;
    }
}
