<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\UserGroup;
use App\Traits\Admin\ItemConfig;

class UserGroupController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'usergroup';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.users.usergroups');
	$this->model = new UserGroup;
    }

    /**
     * Show the role list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->model->getItems($request);
	$rows = $this->getRows($columns, $items);
	$url = ['route' => 'admin.users.usergroups', 'item_name' => 'usergroup', 'query' => $request->query()];
	$query = $request->query();

        return view('admin.users.usergroups.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    public function create(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form', ['destroy']);
	$query = $request->query();

        return view('admin.users.usergroups.form', compact('fields', 'actions', 'query'));
    }

    public function edit(Request $request, $id)
    {
        $userGroup = UserGroup::findOrFail($id);
        $fields = $this->getFields($userGroup);
        $actions = $this->getActions('form');
	$query = $queryWithId = $request->query();
	$queryWithId['usergroup'] = $id;

        return view('admin.users.usergroups.form', compact('userGroup', 'fields', 'actions', 'query', 'queryWithId'));
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
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.usergroups.index', $query)->with('success', $message);
	}

	$query['usergroup'] = $group->id;

	return redirect()->route('admin.users.usergroups.edit', $query)->with('success', $message);
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
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.usergroups.index', $query)->with('success', $message);
	}

	$query['usergroup'] = $group->id;

	return redirect()->route('admin.users.usergroups.edit', $query)->with('success', $message);
    }

    public function destroy(Request $request, $id, $redirect = true)
    {
	$group = UserGroup::findOrFail($id);
	$group->users()->detach();
	//$group->delete();

	if (!$redirect) {
	    return;
	}

	return redirect()->route('admin.users.usergroups.index', $request->query())->with('success', 'User group successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
        foreach ($request->input('ids') as $id) {
	    $this->destroy($request, $id, false);
	}

	return redirect()->route('admin.users.usergroups.index', $request->query())->with('success', count($request->input('ids')).' User group(s) successfully deleted.');
    }
}
