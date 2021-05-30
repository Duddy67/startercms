<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserGroup;
use App\Traits\Admin\ItemConfig;

class UserGroupsController extends Controller
{
    use ItemConfig;


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
	$this->itemName = 'usergroup';
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
        $userGroups = UserGroup::all();
	$rows = $this->getRows($columns, $userGroups);

        return view('admin.usergroups.list', compact('userGroups', 'columns', 'rows', 'actions'));
    }

    public function create()
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form', ['destroy']);

        return view('admin.usergroups.form', compact('fields', 'actions'));
    }

    public function edit($id)
    {
        $userGroup = UserGroup::findById($id);
        $fields = $this->getFields($userGroup);
        $actions = $this->getActions('form');

        return view('admin.usergroups.form', compact('userGroup', 'fields', 'actions'));
    }

    //
}
