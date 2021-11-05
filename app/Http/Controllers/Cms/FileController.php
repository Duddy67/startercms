<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cms\Document;
use App\Traits\Admin\ItemConfig;


class FileController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'document';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'cms';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
	$this->model = new Document;
    }

    /**
     * Show the document list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->model->getFileManagerItems($request);
	$rows = $this->getRows($columns, $items);
	$query = $request->query();

	$url = ['route' => 'admin.files', 'item_name' => 'document', 'query' => $query];

        return view('admin.files.list', compact('items', 'columns', 'actions', 'rows', 'query', 'url', 'filters'));
    }
}
