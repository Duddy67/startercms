<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menus\Menu;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Http\Requests\Menus\Menus\StoreRequest;
use App\Http\Requests\Menus\Menus\UpdateRequest;

class MenuController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'menu';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'menus';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.menus.menus');
	$this->model = new Menu;
    }

    /**
     * Show the menu list.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Gather the needed data to build the item list.
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->model->getItems($request);
	$rows = $this->getRows($columns, $items);
	$query = $request->query();
	$url = ['route' => 'admin.menus.menus', 'item_name' => 'menu', 'query' => $query];

        return view('admin.menus.menus.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }
}
