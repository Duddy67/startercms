<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Setting;
use App\Traits\Admin\ItemConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Cache;


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
    public function index(Request $request, $tab = null)
    {
        $fields = $this->getFields();
        $actions = $this->getActions('form');
        $query = $request->query();
        $data = Setting::getData();
        $tab = ($tab) ? $tab : 'posts';

        return view('admin.blog.settings.form', compact('fields', 'actions', 'data', 'tab', 'query'));
    }

    /**
     * Update the blog parameters.
     *
     * @param  Request  $request
     * @return Response
     */
    public function update(Request $request)
    {
        $post = $request->except('_token', '_method', '_tab');
        $this->truncateSettings();

        foreach ($post as $group => $params) {
          foreach ($params as $key => $value) {
              Setting::create(['group' => $group, 'key' => $key, 'value' => $value]);
          }
        }

        return redirect()->route('admin.blog.settings.index', array_merge($request->query(), ['tab' => $request->input('_tab')]))->with('success', __('messages.general.update_success'));
    }

    /**
     * Empties the setting table.
     *
     * @return void
     */
    private function truncateSettings()
    {
        Schema::disableForeignKeyConstraints();
        DB::table('blog_settings')->truncate();
        Schema::enableForeignKeyConstraints();

        Artisan::call('cache:clear');
    }

    /*
     * Sets field values specific to the General model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\User\User  $user
     * @return void
     */
    private function setFieldValues(&$fields)
    {
        // Specific operations here...
    }
}
