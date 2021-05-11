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
        $users = $this->model->getItems();

        return view('admin.users', compact('users', 'columns'));
    }
}
