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
	$items = $this->model->getAllFileManagerItems($request);
	$rows = $this->getRows($columns, $items, ['preview']);
	$this->setRowValues($rows, $columns, $items);
	$query = $request->query();

	$url = ['route' => 'admin.files', 'item_name' => 'document', 'query' => $query];

        return view('admin.files.list', compact('items', 'columns', 'actions', 'rows', 'query', 'url', 'filters'));
    }

    /**
     * Show the batch form (into an iframe).
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function batch(Request $request)
    {
        $fields = $this->getSpecificFields(['owned_by']);
        $actions = $this->getActions('batch');
	$query = $request->query();
	$route = 'admin.files';

        return view('admin.share.batch', compact('fields', 'actions', 'query', 'route'));
    }

    /**
     * Updates the owned_by (ie: item_id) parameter of one or more documents.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massUpdate(Request $request)
    {
        $updates = 0;
	$messages = [];

        foreach ($request->input('ids') as $key => $id) {
	    $document = Document::findOrFail($id);
	    $document->item_id = $request->input('owned_by');
	    $document->save();
	    $updates++;
	}

	$messages['success'] = __('messages.generic.mass_update_success', ['number' => $updates]);

	return redirect()->route('admin.files.index')->with($messages);
    }


    /*
     * Sets the row values specific to the Document model.
     *
     * @param  Array  $rows
     * @param  Array of stdClass Objects  $columns
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $groups
     * @return void
     */
    private function setRowValues(&$rows, $columns, $documents)
    {
        foreach ($documents as $key => $document) {
	    foreach ($columns as $column) {
	        if ($column->name == 'file_name') {
		    $rows[$key]->file_name = '<a href="'.url('/').$document->getUrl().'" target="_blank">'.$document->file_name.'</a>';
		}

	        if ($column->name == 'preview') {
		    $rows[$key]->preview = view('partials.documents.preview', compact('documents', 'key'));
		}
	    }
	}
    }
}
