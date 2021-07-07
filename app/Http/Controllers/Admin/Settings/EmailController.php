<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Settings\Email;
use App\Traits\Admin\ItemConfig;

class EmailController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'email';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'settings';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        //$this->middleware('admin.settings.emails');
	$this->model = new Email;
    }

    /**
     * Show the role list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->model->getItems($request);
	$rows = $this->getRows($columns, $items);
	$query = $request->query();
	$url = ['route' => 'admin.settings.emails', 'item_name' => 'email', 'query' => $query];

        return view('admin.settings.emails.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    public function create(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form', ['destroy']);
	$query = $request->query();

        return view('admin.settings.emails.form', compact('fields', 'actions', 'query'));
    }

    public function edit(Request $request, $id)
    {
        $email = Email::findOrFail($id);
        $fields = $this->getFields($email);
        $actions = $this->getActions('form');
	$query = $queryWithId = $request->query();
	$queryWithId['email'] = $id;

        return view('admin.settings.emails.form', compact('email', 'fields', 'actions', 'query', 'queryWithId'));
    }

    /**
     * Update the specified user email.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
	$email = Email::findOrFail($id);

        /*$this->validate($request, [
	    'name' => [
		'required',
		'regex:/^[a-z0-9-]{3,}$/',
		Rule::unique('emails')->ignore($id)
	    ],
	]);*/

	$email->code = $request->input('code');
	$email->subject = $request->input('subject');
	$email->body_html = $request->input('body_html');
	$email->body_text = $request->input('body_text');
	$email->plain_text = ($request->input('format') == 'plain_text') ? 1 : 0;
//file_put_contents('debog_file.txt', print_r($request->all(), true));
	$email->save();

	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.settings.emails.index', $query)->with('success', __('messages.emails.update_success'));
	}

	$query['email'] = $email->id;

	return redirect()->route('admin.settings.emails.edit', $query)->with('success', __('messages.emails.update_success'));
    }

    public function store(Request $request)
    {
        /*$this->validate($request, [
	    'name' => [
		'required',
		'regex:/^[a-z0-9-]{3,}$/',
		'unique:emails'
	    ],
	]);*/

	$email = Email::create(['code' => $request->input('code'),
				'subject' => $request->input('subject'),
				'body_html' => $request->input('body_html'),
				'body_text' => $request->input('body_text'),
	]);

	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.settings.emails.index', $query)->with('success', __('messages.emails.create_success'));
	}

	$query['email'] = $email->id;

	return redirect()->route('admin.settings.emails.edit', $query)->with('success', __('messages.emails.create_success'));
    }

    public function destroy(Request $request, $id, $redirect = true)
    {
	$email = Email::findOrFail($id);
	$code = $email->code;
	$email->delete();

	if (!$redirect) {
	    return;
	}

	return redirect()->route('admin.settings.emails.index', $request->query())->with('success', __('messages.emails.delete_success', ['name' => $name]));
    }

    public function massDestroy(Request $request)
    {
        foreach ($request->input('ids') as $id) {
	    $this->destroy($request, $id, false);
	}

	return redirect()->route('admin.settings.emails.index', $request->query())->with('success', __('messages.emails.delete_list_success', ['number' => count($request->input('ids'))]));
    }
}
