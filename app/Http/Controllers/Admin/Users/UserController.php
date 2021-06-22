<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use App\Traits\Admin\ItemConfig;
use App\Traits\Email;

class UserController extends Controller
{
    use ItemConfig, Email;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'user';

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
        $this->middleware('admin.users.users');
	$this->model = new User;
    }

    /**
     * Show the user list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
        $items = $this->model->getItems($request);
	$rows = $this->getRows($columns, $items, ['roles']);
	$this->setRowValues($rows, $columns, $items);
	$url = ['route' => 'admin.users.users', 'item_name' => 'user', 'query' => $request->query()];
	$query = $request->query();

        return view('admin.users.users.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    public function create(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$query = $request->query();

        return view('admin.users.users.form', compact('fields', 'actions', 'query'));
    }

    public function edit(Request $request, $id)
    {
        $user = User::findOrFail($id);

	if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
	    return redirect()->route('admin.users.users.index')->with('error', __('messages.users.edit_user_not_auth'));
	}

        $fields = $this->getFields($user, ['password', 'password_confirmation']);
	// Users cannot delete their own account.
	$except = (auth()->user()->id == $user->id) ? ['destroy'] : [];
        $actions = $this->getActions('form', $except);
	$query = $queryWithId = $request->query();
	$queryWithId['user'] = $id;

        return view('admin.users.users.form', compact('user', 'fields', 'actions', 'query', 'queryWithId'));
    }

    /**
     * Update the specified user.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
	$user = User::findOrFail($id);

	if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
	    return redirect()->route('admin.users.users.edit', $user->id)->with('error',  __('messages.users.update_user_not_auth'));
	}

        $this->validate($request, [
	    'name' => 'bail|required|between:5,25|regex:/^[\pL\s\-]+$/u',
	    'email' => ['bail', 'required', 'email',
			Rule::unique('users')->ignore($id)
	    ],
	    'password' => 'nullable|confirmed|min:8'
	]);

	$user->name = $request->input('name');
	$user->email = $request->input('email');

	if ($request->input('password') !== null) {
	    $user->password = Hash::make($request->input('password'));
	}

	// Users cannot modify the role attribute in their own account.
	if (auth()->user()->id != $user->id) {
	    $user->syncRoles($request->input('role'));
	}

	if ($request->input('groups') !== null) {
	    $user->groups()->sync($request->input('groups'));
	}
	else {
	    // Remove all groups for this user.
	    $user->groups()->sync([]);
	}

	$user->save();
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.users.index', $query)->with('success', __('messages.users.update_success'));
	}

	$query['user'] = $user->id;

	return redirect()->route('admin.users.users.edit', $query)->with('success', __('messages.users.update_success'));
    }

    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => 'bail|required|between:5,25|regex:/^[\pL\s\-]+$/u',
	    'email' => 'bail|required|email|unique:users',
	    'password' => 'required|confirmed|min:8',
	    'role' => 'required'
	]);

	$user = User::create([
	    'name' => $request->input('name'),
	    'email' => $request->input('email'),
	    'password' => Hash::make($request->input('password')),
	]);

	$user->assignRole($request->input('role'));

	if ($request->input('groups') !== null) {
	    $user->groups()->attach($request->input('groups'));
	}

	$this->sendRegistrationNotification($user);

	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.users.index', $query)->with('success', __('messages.users.create_success'));
	}

	$query['user'] = $user->id;

	return redirect()->route('admin.users.users.edit', $query)->with('success', __('messages.users.create_success'));
    }

    public function destroy(Request $request, $id)
    {
	$user = User::findOrFail($id);

	if (!auth()->user()->canDelete($user)) {
	    return redirect()->route('admin.users.users.edit', $user->id)->with('error', __('messages.users.delete_user_not_auth'));
	}

	$user->groups()->detach();
	$name = $user->name;
	//$user->delete();

	return redirect()->route('admin.users.users.index', $request->query())->with('success', __('messages.users.delete_success', ['name' => $name]));
    }

    public function massDestroy(Request $request)
    {
        if ($request->input('ids') !== null) {

	    foreach ($request->input('ids') as $key => $id) {
		$user = User::findOrFail($id);

		if (!auth()->user()->canDelete($user)) {
		    // Informs about the users previously deleted.
		    if ($key > 0) {
			$request->session()->flash('success', __('messages.users.delete_list_success', ['number' => $key]));
		    }

		    return redirect()->route('admin.users.users.index')->with('error', __('messages.users.delete_list_not_auth', ['name' => $user->name]));
		}

		$user->groups()->detach();
		//$user->delete();
	    }
	}

	return redirect()->route('admin.users.users.index', $request->query())->with('success', __('messages.users.delete_list_success', ['number' => count($request->input('ids'))]));
    }

    /*
     * Sets row values specific to the User model.
     */
    private function setRowValues(&$rows, $columns, $users)
    {
        foreach ($users as $key => $user) {
	    foreach ($columns as $column) {
	        if ($column->name == 'role') {
		    $roles = $user->getRoleNames();
		    $rows[$key]['role'] = $roles[0];
		}

	        if ($column->name == 'groups') {
		    $groups = $user->groups()->pluck('name')->toArray();
		    $groups = (!empty($groups)) ? implode(',', $groups) : '-';
		    $rows[$key]['groups'] = $groups;
		}
	    }
	}
    }

    /*
     * Sets field values specific to the User model.
     */
    private function setFieldValues(&$fields, $user)
    {
	// Specific operations here...
    }
}
