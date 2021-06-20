<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Settings\General;
use App\Traits\Admin\ItemConfig;

class GeneralController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'general';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'Settings';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.settings.general');
	$this->model = new General;
    }

    /**
     * Show the general settings.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$query = $request->query();
	$data = $this->model->getData();

        return view('admin.settings.general.form', compact('fields', 'actions', 'data', 'query'));
    }

    /**
     * Update the general parameters.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->except('_token', '_method');

	foreach ($post as $group => $params) {
	  foreach ($params as $key => $value) {
	      General::updateOrCreate(['group' => $group, 'key' => $key], ['value' => $value]);
	  }
	}

	return redirect()->route('admin.settings.general.index', $request->query())->with('success', __('messages.general.update_success'));
    }

    /*
     * Sets field values specific to the General model.
     */
    private function setFieldValues(&$fields)
    {
	// Specific operations here...
    }
}
