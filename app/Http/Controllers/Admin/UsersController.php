<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Traits\Admin\ItemConfig;

class UsersController extends Controller
{
    use ItemConfig;

    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.users');
	$this->model = new User;
	//
	$this->itemName = 'user';
	$this->itemModel = '\App\Models\User';
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
	$url = ['route' => 'admin.users', 'item_name' => 'user', 'query' => $request->query()];
	$query = $request->query();

        return view('admin.users.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    public function create(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$query = $request->query();

        return view('admin.users.form', compact('fields', 'actions', 'query'));
    }

    public function edit(Request $request, $id)
    {
        $user = User::findOrFail($id);

	if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
	    return redirect()->route('admin.users.index')->with('error', 'You are not allowed to edit this user.');
	}

        $fields = $this->getFields($user, ['password', 'password_confirmation']);
	// Users cannot delete their own account.
	$except = (auth()->user()->id == $user->id) ? ['destroy'] : [];
        $actions = $this->getActions('form', $except);
	$query = $queryWithId = $request->query();
	$queryWithId['user'] = $id;

        return view('admin.users.form', compact('user', 'fields', 'actions', 'query', 'queryWithId'));
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
	    return redirect()->route('admin.users.edit', $user->id)->with('error', 'You are not allowed to update this user.');
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

	$message = 'User successfully updated.';
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.index', $query)->with('success', $message);
	}

	$query['user'] = $user->id;

	return redirect()->route('admin.users.edit', $query)->with('success', $message);
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

	$message = 'User successfully added.';
	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.index', $query)->with('success', $message);
	}

	$query['user'] = $user->id;

	return redirect()->route('admin.users.edit', $query)->with('success', $message);
    }

    public function destroy(Request $request, $id)
    {
	$user = User::findOrFail($id);

	if (!auth()->user()->canDelete($user)) {
	    return redirect()->route('admin.users.edit', $user->id)->with('error', 'You are not allowed to delete this user.');
	}

	$user->groups()->detach();
	//$user->delete();

	return redirect()->route('admin.users.index', $request->query())->with('success', 'The user has been successfully deleted.');
    }

    public function massDestroy(Request $request)
    {
        if ($request->input('ids') !== null) {

	    foreach ($request->input('ids') as $key => $id) {
		$user = User::findOrFail($id);

		if (!auth()->user()->canDelete($user)) {
		    // Informs about the users previously deleted.
		    if ($key > 0) {
			$request->session()->flash('success', $key.' user(s) has been successfully deleted.');
		    }

		    return redirect()->route('admin.users.index')->with('error', 'You are not allowed to delete this user: '.$user->name);
		}

		$user->groups()->detach();
		//$user->delete();
	    }
	}

	return redirect()->route('admin.users.index', $request->query())->with('success', count($request->input('ids')).' user(s) has been successfully deleted.');
    }

    /*
     * Sets row values specific to the User model.
     */
    private function setRowValues(&$rows, $columns, $users)
    {
        foreach ($users as $key => $user) {
	    foreach ($columns as $column) {
	        if ($column->id == 'role') {
		    $roles = $user->getRoleNames();
		    $rows[$key][$column->id] = $roles[0];
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
