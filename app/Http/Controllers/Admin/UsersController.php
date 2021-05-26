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
    public function index()
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $users = $this->model->getItems();
	$rows = $this->getRows($columns, $users, ['roles']);
	$this->setRowValues($rows, $columns, $users);

        return view('admin.users.list', compact('users', 'columns', 'rows', 'actions'));
    }

    public function create()
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');

        return view('admin.users.form', compact('fields', 'actions'));
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $fields = $this->getFields($user, ['password', 'password_confirmation']);
        $actions = $this->getActions('form');
	//$roles = $user->getRoleType();
//var_dump($roles);
        return view('admin.users.form', compact('user', 'fields', 'actions'));
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

	if (!User::canUpdate($user)) {
	    return redirect()->route('admin.users.index')->with('error', 'You are not allowed to update this user.');
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

	if (!$user->hasRole('super-admin')) {
	    $user->syncRoles($request->input('role'));
	}

	$user->save();

	$message = 'User successfully updated.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.index')->with('success', $message);
	}

	return redirect()->route('admin.users.edit', $user->id)->with('success', $message);
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
	    'password' => Hash::make($request->input('name')),
	]);

	$user->assignRole($request->input('role'));

	$message = 'User successfully added.';

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.index')->with('success', $message);
	}

	return redirect()->route('admin.users.edit', $user->id)->with('success', $message);
    }

    public function destroy($id, $redirect = true)
    {
	$user = User::findOrFail($id);
//file_put_contents('debog_file.txt', print_r($id, true), FILE_APPEND);

	if (!User::canDelete($user)) {
	    return ($redirect) ? redirect()->route('admin.users.index')->with('error', 'You are not allowed to delete this user.') : false;
	}

	return ($redirect) ? redirect()->route('admin.users.index')->with('success', 'The user has been successfully deleted.') : true;
    }

    public function massDestroy(Request $request)
    {
        if ($request->input('ids') !== null) {
	    foreach ($request->input('ids') as $id) {
	      if (!$this->destroy($id, false)) {
		  return redirect()->route('admin.users.index')->with('error', 'You are not allowed to delete this user');
	      }
	    }
	}

	$request->session()->flash('warning', 'Testing a warning message');
	$request->session()->flash('success', 'Success is in the eye of the beer holder');
	//return redirect()->route('admin.users.index')->with('success', count($request->input('ids')).' user(s) has been successfully deleted.');
	return redirect()->route('admin.users.index');
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
