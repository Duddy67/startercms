<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menus\Menu;
use App\Models\Users\Group;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Http\Requests\Menus\Menus\StoreRequest;
use App\Http\Requests\Menus\Menus\UpdateRequest;
use Illuminate\Support\Str;

use App\Models\Menus\MenuItem;

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

    /**
     * Show the form for creating a new menu.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(Request $request)
    {
        // Gather the needed data to build the form.

        $fields = $this->getFields(null, ['updated_by', 'created_at', 'updated_at', 'owner_name']);
        $actions = $this->getActions('form', ['destroy']);
	$query = $request->query();

        return view('admin.menus.menus.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified menu.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $menu = Menu::select('menus.*', 'users.name as owner_name', 'users2.name as modifier_name')
			->leftJoin('users', 'menus.owned_by', '=', 'users.id')
			->leftJoin('users as users2', 'menus.updated_by', '=', 'users2.id')
			->findOrFail($id);

	if (!$menu->canAccess()) {
	    return redirect()->route('admin.menus.menus.index')->with('error',  __('messages.generic.access_not_auth'));
	}

	if ($menu->checked_out && $menu->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.menus.menus.index')->with('error',  __('messages.generic.checked_out'));
	}

	$menu->checkOut();

        // Gather the needed data to build the form.
	
	$except = (auth()->user()->getRoleLevel() > $menu->getOwnerRoleLevel() || $menu->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];

	if ($menu->updated_by === null) {
	    array_push($except, 'updated_by', 'updated_at');
	}

        $fields = $this->getFields($menu, $except);
        $this->setFieldValues($fields, $menu);
	$except = (!$menu->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        $actions = $this->getActions('form', $except);
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['menu' => $id]);

        return view('admin.menus.menus.form', compact('menu', 'fields', 'actions', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Menus\Menu  $menu (optional)
     * @return Response
     */
    public function cancel(Request $request, Menu $menu = null)
    {
        if ($menu) {
	    $menu->checkIn();
	}

	return redirect()->route('admin.menus.menus.index', $request->query());
    }

    /**
     * Update the specified menu.
     *
     * @param  \App\Http\Requests\Menus\Menu\UpdateRequest  $request
     * @param  \App\Models\Menus\Menu  $menu
     * @return Response
     */
    public function update(UpdateRequest $request, Menu $menu)
    {
	if ($menu->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.menus.menus.index', $request->query())->with('error',  __('messages.generic.user_id_does_not_match'));
	}

	if (!$menu->canEdit()) {
	    return redirect()->route('admin.menus.menus.edit', array_merge($request->query(), ['menu' => $menu->id]))->with('error',  __('messages.generic.edit_not_auth'));
	}

	$menu->title = $request->input('title');
	$menu->updated_by = auth()->user()->id;

	if ($menu->canChangeAccessLevel()) {
	    $menu->access_level = $request->input('access_level');
	}

	if ($menu->canChangeAttachments()) {
	    $menu->owned_by = $request->input('owned_by');

	    // N.B: Get also the private groups (if any) that are not returned by the form (as they're not available).
	    $groups = array_merge($request->input('groups', []), Group::getPrivateGroups($menu));

	    if (!empty($groups)) {
		$menu->groups()->sync($groups);
	    }
	    else {
		// Remove all groups for this menu.
		$menu->groups()->sync([]);
	    }
	}

	if ($menu->canChangeStatus()) {
	    $menu->status = $request->input('status');
	}

	$menu->save();

        if ($request->input('_close', null)) {
	    $menu->checkIn();
	    // Redirect to the list.
	    return redirect()->route('admin.menus.menus.index', $request->query())->with('success', __('messages.menus.update_success'));
	}

	return redirect()->route('admin.menus.menus.edit', array_merge($request->query(), ['menu' => $menu->id]))->with('success', __('messages.menus.update_success'));
    }

    /**
     * Store a new menu.
     *
     * @param  \App\Http\Requests\Menus\Menu\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
	$menu = Menu::create([
	  'title' => $request->input('title'), 
	  'code' => $request->input('code'), 
	  'status' => $request->input('status'), 
	  'access_level' => $request->input('access_level'), 
	  'owned_by' => $request->input('owned_by'),
	]);

	if ($request->input('groups') !== null) {
	    $menu->groups()->attach($request->input('groups'));
	}

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.menus.menus.index', $request->query())->with('success', __('messages.menus.create_success'));
	}

	return redirect()->route('admin.menus.menus.edit', array_merge($request->query(), ['menu' => $menu->id]))->with('success', __('messages.menus.create_success'));
    }

    /**
     * Remove the specified menu from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menus\Menu  $menu
     * @return Response
     */
    public function destroy(Request $request, Menu $menu)
    {
        // Prevent the main menu to be deleted. 
	if (!$menu->canDelete() || $menu->code == 'main-menu') {
	    return redirect()->route('admin.menus.menus.edit', array_merge($request->query(), ['menu' => $menu->id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	$name = $menu->name;

	$menu->delete();

	return redirect()->route('admin.menus.menus.index', $request->query())->with('success', __('messages.menus.delete_success', ['name' => $name]));
    }

    /**
     * Removes one or more menus from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
        $deleted = 0;
	$messages = [];

        // Remove the menus selected from the list.
        foreach ($request->input('ids') as $id) {
	    $menu = Menu::findOrFail($id);

	    // Prevent the main menu to be deleted. 
	    if (!$menu->canDelete() || $menu->code == 'main-menu') {

	        $messages['error'] = __('messages.generic.delete_not_auth'); 

		if ($deleted) {
		    $messages['success'] = __('messages.menus.mass_delete_success', ['number' => $deleted]);
		}

		return redirect()->route('admin.menus.menus.index', $request->query())->with($messages);
	    }

	    $menu->delete();

	    $deleted++;
	}

	return redirect()->route('admin.menus.menus.index', $request->query())->with('success', __('messages.menus.delete_list_success', ['number' => $deleted]));
    }

    /**
     * Checks in one or more menus.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = CheckInCheckOut::checkInMultiple($request->input('ids'), '\\App\\Models\\Menus\\Menu');

	return redirect()->route('admin.menus.menus.index', $request->query())->with($messages);
    }

    /**
     * Publishes one or more menus.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massPublish(Request $request)
    {
        $published = 0;

        foreach ($request->input('ids') as $id) {
	    $menu = Menu::findOrFail($id);

	    if (!$menu->canChangeStatus()) {
	      return redirect()->route('admin.menus.menus.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.mass_publish_not_auth'), 
		      'success' => __('messages.menus.publish_list_success', ['number' => $published])
		  ]);
	    }

	    $menu->status = 'published';

	    $menu->save();

	    $published++;
	}

	return redirect()->route('admin.menus.menus.index', $request->query())->with('success', __('messages.menus.publish_list_success', ['number' => $published]));
    }

    /**
     * Unpublishes one or more menus.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massUnpublish(Request $request)
    {
        $unpublished = 0;

        foreach ($request->input('ids') as $id) {
	    $menu = Menu::findOrFail($id);

	    if (!$menu->canChangeStatus()) {
	      return redirect()->route('admin.menus.menus.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.mass_unpublish_not_auth'), 
		      'success' => __('messages.menus.unpublish_list_success', ['number' => $unpublished])
		  ]);
	    }

	    $menu->status = 'unpublished';

	    $menu->save();

	    $unpublished++;
	}

	return redirect()->route('admin.menus.menus.index', $request->query())->with('success', __('messages.menus.unpublish_list_success', ['number' => $unpublished]));
    }

    /*
     * Sets field values specific to the Menu model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Menus\Menu $menu
     * @return void
     */
    private function setFieldValues(&$fields, $menu)
    {
        foreach ($fields as $field) {
	    if ($field->name == 'code') {
	        $field->extra = ['disabled'];
	    }
	}
    }
}
