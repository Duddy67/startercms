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
//file_put_contents('debog_file.txt', print_r($rows, true));

        return view('admin.usergroups.list', compact('columns', 'rows', 'actions'));
    }

    public function create()
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form', ['destroy']);

        return view('admin.usergroups.form', compact('fields', 'actions'));
    }

    public function edit($id)
    {
        $userGroup = UserGroup::findOrFail($id);
        $fields = $this->getFields($userGroup);
        $actions = $this->getActions('form');

        return view('admin.usergroups.form', compact('userGroup', 'fields', 'actions'));
    }

    /**
     * Update the specified user group.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
	$group = UserGroup::findOrFail($id);

        $this->validate($request, [
	    'name' => [
		'required',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('user_groups')->ignore($id)
	    ],
	]);

	$group->name = $request->input('name');
	$group->save();

	$message = 'User group successfully updated.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.usergroups.index')->with('success', $message);
	}

	return redirect()->route('admin.usergroups.edit', $group->id)->with('success', $message);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => [
		'required',
		'regex:/^[a-z0-9-]{3,}$/',
		'unique:user_groups'
	    ],
	]);

	$group = UserGroup::create(['name' => $request->input('name')]);

	$message = 'User group successfully added.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.usergroups.index')->with('success', $message);
	}

	return redirect()->route('admin.usergroups.edit', $group->id)->with('success', $message);
    }

    public function destroy($id)
    {
	$group = UserGroup::findOrFail($id);
	$group->delete();

	return redirect()->route('admin.usergroups.index')->with('success', 'User group successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
	Permission::destroy($request->input('ids'));

	return redirect()->route('admin.usergroups.index')->with('success', count($request->input('ids')).' User group(s) successfully deleted.');
    }
}
