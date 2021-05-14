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
        $toolbar = $this->getToolbar();
        $users = $this->model->getItems();
	$rows = $this->getRows($columns);

        return view('admin.users', compact('users', 'columns', 'rows', 'toolbar'));
    }

    public function getRows($columns)
    {
        $rows = [];
        $users = $this->model->getItems();

        foreach ($users as $user) {
	    $row = array('item_id' => $user->id);
	    foreach ($columns as $column) {
	        if ($column->id == 'roles') {
		    $row[$column->id] = $user->getRoleNames();
		}
		else {
		    $row[$column->id] = $user->{$column->id};
		}
	    }

	    $rows[] = $row;
	}

	return $rows;
    }
}
