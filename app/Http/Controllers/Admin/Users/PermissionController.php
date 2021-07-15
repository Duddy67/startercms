<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\RolesPermissions;
use Spatie\Permission\Models\Permission;


class PermissionController extends Controller
{
    use ItemConfig, RolesPermissions;

    /*
     * Name of the model.
     */
    protected $modelName = 'permission';

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
        $this->middleware('admin.users.permissions');
    }

    /**
     * Show the permission list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Gather the needed data to build the permission list.
        $actions = $this->getActions('list');
        $list = $this->getList();

        return view('admin.users.permissions.list', compact('list', 'actions'));
    }

    /*
     * Creates or updates the list.
     *
     * @param  Request  $request
     * @return Response
     */
    public function build(Request $request)
    {
	$this->buildPermissions($request);
	return redirect()->route('admin.users.permissions.index');
    }

    /*
     * Rebuilds all of the list.
     *
     * @param  Request  $request
     * @return Response
     */
    public function rebuild(Request $request)
    {
	$this->buildPermissions($request, true);
	return redirect()->route('admin.users.permissions.index');
    }

    /*
     * Set the list of permissions to display.
     *
     * @return Array
     */
    private function getList()
    {
	$permList = $this->getPermissionList();

	$list = [];

	foreach ($permList as $section => $permissions) {
	    $list[$section] = [];

	    foreach ($permissions as $permission) {
		// Check for missing permissions.
		if (Permission::where('name', $permission->name)->first() === null) {
		    $list[$section][] = $permission->name.' '.__('messages.permissions.missing_alert');
		    continue;
		}

		$list[$section][] = $permission->name;
	    }
	}

	return $list;
    }
}
