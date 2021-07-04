<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cms\Document;
use App\Traits\Admin\ItemConfig;

class DocumentController extends Controller
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
        $filters = $this->getFilters($request);
	$items = $this->model->getItems($request);
	$rows = $this->getRows($columns, $items);
	$query = $request->query();

	$url = ['route' => 'cms.documents', 'item_name' => 'document', 'query' => $query];

        return view('cms.documents.list', compact('items', 'columns', 'rows', 'query', 'url', 'filters'));
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('upload') && $request->file('upload')->isValid()) {
	    $document = new Document;
	    $document->upload($request->file('upload'), 'user', 'upload');
	    auth()->user()->documents()->save($document);
	}

	return redirect()->route('cms.documents.index')->with('success', __('messages.documents.create_success'));
    }

    public function destroy(Request $request)
    {
	$document = Document::findOrFail($request->input('document_id', null));

	$name = $document->file_name;
	$document->delete();

	return redirect()->route('cms.documents.index', $request->query())->with('success', __('messages.documents.delete_success', ['name' => $name]));
    }

}
