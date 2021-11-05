<?php

namespace App\Http\Controllers\Admin\Menus;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menus\MenuItem;
use App\Models\Users\Group;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Http\Requests\Menus\MenuItems\StoreRequest;
use App\Http\Requests\Menus\MenuItems\UpdateRequest;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
    use ItemConfig;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'menuitem';

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
        $this->middleware('admin.menus.menuitems');
	$this->model = new MenuItem;
    }

    /**
     * Show the menu item list.
     *
     * @param  Request  $request
     * @param  string   $code
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request, $code)
    {
        // Gather the needed data to build the item list.
        $columns = $this->getColumns();
        $actions = $this->getActions('list');
        $filters = $this->getFilters($request);
	$items = $this->model->getItems($request, $code);
	$rows = $this->getRowTree($columns, $items);
	$query = $request->query();
	$query['code'] = $code;

	$url = ['route' => 'admin.menus.menuitems', 'item_name' => 'menuItem', 'query' => $query];

        return view('admin.menus.menuitems.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new item menu.
     *
     * @param  Request  $request
     * @param  string   $code
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(Request $request, $code)
    {
        // Gather the needed data to build the form.

        $fields = $this->getFields(null, ['updated_by', 'created_at', 'updated_at', 'owner_name']);
        $actions = $this->getActions('form', ['destroy']);
	$query = array_merge($request->query(), ['code' => $code]);

        return view('admin.menus.menuitems.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified menu item.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $code, $id)
    {
        $menuItem = MenuItem::select('menu_items.*', 'users.name as owner_name')
			      ->selectRaw('IFNULL(users2.name, ?) as modifier_name', [__('labels.generic.unknown_user')])
			      ->leftJoin('users', 'menu_items.owned_by', '=', 'users.id')
			      ->leftJoin('users as users2', 'menu_items.updated_by', '=', 'users2.id')
			      ->findOrFail($id);

	if (!$menuItem->canAccess()) {
	    return redirect()->route('admin.menus.menuitems.index')->with('error',  __('messages.generic.access_not_auth'));
	}

	if ($menuItem->checked_out && $menuItem->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.menus.menuitems.index')->with('error',  __('messages.generic.checked_out'));
	}

	$menuItem->checkOut();

        // Gather the needed data to build the form.
	
	$except = (auth()->user()->getRoleLevel() > $menuItem->getOwnerRoleLevel() || $menuItem->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];

	if ($menuItem->updated_by === null) {
	    array_push($except, 'updated_by', 'updated_at');
	}
//$target = MenuItem::targets()['blog.category']::where('id', 8)->first();
$target = MenuItem::getTarget('blog.category', $menuItem->url);
echo $target->name;
        $fields = $this->getFields($menuItem, $except);
	$this->setFieldValues($fields, $menuItem);
	$except = (!$menuItem->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        $actions = $this->getActions('form', $except);
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['code' => $code, 'menuItem' => $id]);

        return view('admin.menus.menuitems.form', compact('menuItem', 'fields', 'actions', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Menus\MenuItem $menuItem (optional)
     * @return Response
     */
    public function cancel(Request $request, $code, MenuItem $menuItem = null)
    {
        if ($menuItem) {
	    $menuItem->checkIn();
	}

	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]));
    }

    /**
     * Update the specified menu item.
     *
     * @param  \App\Http\Requests\Menus\MenuItem\UpdateRequest  $request
     * @param  string  $code
     * @param  \App\Models\Menus\MenuItem  $menuItem
     * @return Response
     */
    public function update(UpdateRequest $request, $code, MenuItem $menuItem)
    {
	if ($menuItem->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.menus.menuitems.index', $request->query())->with('error',  __('messages.generic.user_id_does_not_match'));
	}

	if (!$menuItem->canEdit()) {
	    return redirect()->route('admin.menus.menuitems.index', $request->query())->with('error',  __('messages.generic.edit_not_auth'));
	}

	$query = array_merge($request->query(), ['code' => $code, 'menuItem' => $menuItem->id]);

	$parent = MenuItem::findOrFail($request->input('parent_id'));

	// Check the selected parent is not the menu item itself or a descendant.
	if ($menuItem->id == $request->input('parent_id') || $parent->isDescendantOf($menuItem)) {
	    return redirect()->route('admin.menus.menuitems.edit', $query)->with('error',  __('messages.generic.must_not_be_descendant'));
	}

	if ($parent->access_level == 'private' && $parent->owned_by != auth()->user()->id) {
	    return redirect()->route('admin.menus.menuitems.edit', $query)->with('error',  __('messages.generic.item_is_private', ['name' => $parent->name]));
	}

	$menuItem->title = $request->input('title');
	$menuItem->url = $request->input('url');
	$menuItem->updated_by = auth()->user()->id;

	if ($menuItem->canChangeAttachments()) {

	    if ($menuItem->access_level != 'private') {
		$menuItem->owned_by = $request->input('owned_by');
	    }

	    $groups = array_merge($request->input('groups', []), Group::getPrivateGroups($menuItem));

	    if (!empty($groups)) {
		$menuItem->groups()->sync($groups);
	    }
	    else {
		// Remove all groups for this post.
		$menuItem->groups()->sync([]);
	    }
	}

	if ($menuItem->canChangeAccessLevel()) {

	    if ($menuItem->access_level != 'private') {
		// The access level has just been set to private. Check first for descendants.
		if ($request->input('access_level') == 'private' && !$menuItem->canDescendantsBePrivate()) {
		    return redirect()->route('admin.menus.menuitems.edit', $query)->with('error',  __('messages.generic.descendants_cannot_be_private'));
		}

		if ($request->input('access_level') == 'private' && $menuItem->anyDescendantCheckedOut()) {
		    return redirect()->route('admin.menus.menuitems.edit', $query)->with('error',  __('messages.generic.descendants_checked_out'));
		}

		if ($request->input('access_level') == 'private') {
		    $menuItem->setDescendantAccessToPrivate();
		}
	    }

	    if ($menuItem->access_level != 'private' || ($menuItem->access_level == 'private' && !$menuItem->isParentPrivate())) {
		$menuItem->access_level = $request->input('access_level');
		// N.B: The nested set model is updated automatically.
		$menuItem->parent_id = $request->input('parent_id');
	    }

	    if ($menuItem->access_level == 'private' && $menuItem->isParentPrivate() && $menuItem->owned_by == auth()->user()->id) {
		// Only the owner of the descendants private items can change their parents.
		$menuItem->parent_id = $request->input('parent_id');
	    }
	}

	$menuItem->save();

        if ($request->input('_close', null)) {
	    $menuItem->checkIn();
	    // Redirect to the list.
	    return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with('success', __('messages.menuitems.update_success'));
	}

	return redirect()->route('admin.menus.menuitems.edit', $query)->with('success', __('messages.menuitems.update_success'));
    }

    /**
     * Store a new menu item.
     *
     * @param  \App\Http\Requests\Menus\MenuItem\StoreRequest  $request
     * @param  string  $code
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, $code)
    {
        // Check first for parent id. (N.B: menu items cannot be null as they have a root parent id by default).
	$parent = MenuItem::findOrFail($request->input('parent_id'));

	if ($parent->access_level == 'private' && $parent->owned_by != auth()->user()->id) {
	    return redirect()->route('admin.menus.menuitems.create', array_merge($request->query(), ['code' => $code]))->with('error',  __('messages.generic.item_is_private', ['name' => $parent->name]));
	}

	if ($parent->access_level == 'private' && $request->input('access_level') != 'private') {
	    return redirect()->route('admin.menus.menuitems.create', array_merge($request->query(), ['code' => $code]))->with('error',  __('messages.generic.access_level_must_be_private'));
	}

	if ($parent->access_level == 'private' && $request->input('owned_by') != $parent->owned_by) {
	    return redirect()->route('admin.menus.menuitems.create', array_merge($request->query(), ['code' => $code]))->with('error',  __('messages.generic.owner_must_match_parent_menu_item'));
	}

	$menuItem = MenuItem::create([
	    'title' => $request->input('title'), 
	    'url' => $request->input('url'), 
	    'status' => $request->input('status'), 
	    'access_level' => $request->input('access_level'), 
	    'owned_by' => $request->input('owned_by'),
	    'parent_id' => (empty($request->input('parent_id'))) ? null : $request->input('parent_id'),
	]);

	$parent = MenuItem::findOrFail($menuItem->parent_id);
	$parent->appendNode($menuItem);

	$menuItem->menu_code = $code;
	$menuItem->save();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with('success', __('messages.menuitems.create_success'));
	}

	return redirect()->route('admin.menus.menuitems.edit', array_merge($request->query(), ['code' => $code, 'menuItem' => $menuItem->id]))->with('success', __('messages.menuitems.create_success'));
    }

    /**
     * Remove the specified menu item from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $code
     * @param  \App\Models\Menus\MenuItem $menuItem
     * @return Response
     */
    public function destroy(Request $request, $code, MenuItem $menuItem)
    {
	if (!$menuItem->canDelete() || !$menuItem->canDeleteDescendants()) {
	    return redirect()->route('admin.menus.menuitems.edit', array_merge($request->query(), ['code' => $code, 'menuItem' => $menuItem->id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	$name = $menuItem->name;

	//$menuItem->menuitems()->detach();
	//$menuItem->delete();

	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with('success', __('messages.menuitems.delete_success', ['name' => $name]));
    }

    /**
     * Removes one or more menu items from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $code
     * @return Response
     */
    public function massDestroy(Request $request, $code)
    {
        $deleted = 0;
        // Remove the menu items selected from the list.
        foreach ($request->input('ids') as $id) {
	    $menuItem = MenuItem::findOrFail($id);

	    if (!$menuItem->canDelete() || !$menuItem->canDeleteDescendants()) {
	      return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with(
		  [
		      'error' => __('messages.generic.delete_not_auth'), 
		      'success' => __('messages.menuitems.delete_list_success', ['number' => $deleted])
		  ]);
	    }

	    //$menuItem->menuitems()->detach();
	    $menuItem->delete();

	    $deleted++;
	}

	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with('success', __('messages.menuitems.delete_list_success', ['number' => $deleted]));
    }

    /**
     * Checks in one or more menu items.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $code
     * @return Response
     */
    public function massCheckIn(Request $request, $code)
    {
        $messages = CheckInCheckOut::checkInMultiple($request->input('ids'), '\\App\\Models\\Menus\\MenuItem');

	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with($messages);
    }

    public function massPublish(Request $request, $code)
    {
        $changed = 0;

        foreach ($request->input('ids') as $id) {
	    $menuItem = MenuItem::findOrFail($id);
	    // Cannot published a menu item if its parent is unpublished.
	    if ($menuItem->parent && $menuItem->parent->status == 'unpublished') {
	        continue;
	    }

	    if (!$menuItem->canChangeStatus()) {
	      $messages = ['error' => __('messages.generic.change_status_not_auth')];

	      if ($changed) {
		  $messages['success'] = __('messages.menuitems.change_status_list_success', ['number' => $changed]);
	      }

	      return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with($messages);
	    }

	    $menuItem->status = 'published';
	    $menuItem->save();

	    $changed++;
	}

	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with('success', __('messages.menuitems.change_status_list_success', ['number' => $changed]));
    }

    public function massUnpublish(Request $request, $code)
    {
        $treated = [];
        $changed = 0;

        foreach ($request->input('ids') as $id) {
	    //
	    if (in_array($id, $treated)) {
	        continue;
	    }

	    $menuItem = MenuItem::findOrFail($id);

	    if (!$menuItem->canChangeStatus()) {
	      $messages = ['error' => __('messages.generic.change_status_not_auth')];

	      if ($changed) {
		  $messages['success'] = __('messages.menuitems.change_status_list_success', ['number' => $changed]);
	      }

	      return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with($messages);
	    }

	    $menuItem->status = 'unpublished';
	    $menuItem->save();

	    $changed++;

	    // All the descendants must be unpublished as well.
	    foreach ($menuItem->descendants as $descendant) {
	        $descendant->status = 'unpublished';
		$descendant->save();
		// Prevent this descendant to be treated twice.
		$treated[] = $descendant->id;
	    }
	}

	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]))->with('success', __('messages.menuitems.change_status_list_success', ['number' => $changed]));
    }

    /**
     * Reorders a given menu item a level above.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menus\MenuItem $menuItem
     * @return Response
     */
    public function up(Request $request, $code, MenuItem $menuItem)
    {
	$menuItem->up();
	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]));
    }

    /**
     * Reorders a given menu item a level below.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Menus\MenuItem $menuItem
     * @return Response
     */
    public function down(Request $request, $code, MenuItem $menuItem)
    {
	$menuItem->down();
	return redirect()->route('admin.menus.menuitems.index', array_merge($request->query(), ['code' => $code]));
    }

    /*
     * Sets field values specific to the MenuItem model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Menus\MenuItem $menuItem
     * @return void
     */
    private function setFieldValues(&$fields, $menuItem)
    {
        foreach ($fields as $field) {
            if ($field->name == 'parent_id') {
	        foreach ($field->options as $key => $option) {
		    if ($option['value'] == $menuItem->id) {
		        // Menu item cannot be its own children.
		        $field->options[$key]['extra'] = ['disabled'];
		    }
		}
	    }
        }
    }
}
