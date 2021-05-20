<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
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
	$this->model = new User;
	$this->itemName = 'user';
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
        $this->validate($request, [
	    'name' => 'bail|required|between:5,25|regex:/^[\pL\s\-]+$/u',
	    'email' => 'bail|required|email|unique:users,email',
	    'password' => 'nullable|confirmed|min:8'
	]);

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.index');
	}

        return var_dump($request->all());
    }

    public function store(Request $request)
    {
        $this->validate($request, [
	    'name' => 'bail|required|between:5,25|regex:/^[\pL\s\-]+$/u',
	    'email' => 'bail|required|email|unique:users,email',
	    'password' => 'required|confirmed|min:8'
	]);

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.index');
	}

        return 'store';
    }

    public function destroy($id)
    {
	return redirect()->route('admin.users.index');
        return 'destroy';
    }

    public function massDestroy(Request $request)
    {
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
