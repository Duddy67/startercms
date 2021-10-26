<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Setting;
use App\Traits\Admin\ItemConfig;


class SettingController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'setting';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'Blog';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.blog.settings');
	$this->model = new Setting;
    }


    /**
     * Show the blog settings.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
	$query = $request->query();
	//$data = $this->model->getData();

        return view('admin.blog.settings.form', compact('fields', 'actions', 'query'));
    }
}
