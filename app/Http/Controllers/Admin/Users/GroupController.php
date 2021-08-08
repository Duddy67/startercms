<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Users\Group;
use App\Models\Users\User;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Http\Requests\Users\Group\StoreRequest;
use App\Http\Requests\Users\Group\UpdateRequest;


class GroupController extends Controller
{
    use ItemConfig, CheckInCheckOut;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'group';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'users';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.users.groups');
	$this->model = new Group;
    }

    /**
     * Show the group list.
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
	$url = ['route' => 'admin.users.groups', 'item_name' => 'group', 'query' => $query];

        return view('admin.users.groups.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new group.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(Request $request)
    {
        // Gather the needed data to build the form.
        $fields = $this->getFields(null, ['updated_by', 'owner_name']);
        $actions = $this->getActions('form', ['destroy']);
	$query = $request->query();

        return view('admin.users.groups.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified group.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $group = Group::select('groups.*', 'users.name as owner_name', 'users2.name as modifier_name')
			->leftJoin('users', 'groups.created_by', '=', 'users.id')
			->leftJoin('users as users2', 'groups.updated_by', '=', 'users2.id')
			->findOrFail($id);

	if (!$group->canAccess()) {
	    return redirect()->route('admin.users.groups.index')->with('error',  __('messages.generic.access_not_auth'));
	}

	if ($group->checked_out && $group->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.users.groups.index')->with('error',  __('messages.generic.checked_out'));
	}

	$this->checkOut($group);

        // Gather the needed data to build the form.
	
	$except = ($group->role_level > auth()->user()->getRoleLevel()) ? ['created_by'] : ['owner_name'];

	if ($group->updated_by === null) {
	    array_push($except, 'updated_by', 'updated_at');
	}

        $fields = $this->getFields($group, $except);
        $actions = $this->getActions('form');
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['group' => $id]);

        return view('admin.users.groups.form', compact('group', 'fields', 'actions', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Users\Group $group (optional)
     * @return Response
     */
    public function cancel(Request $request, Group $group = null)
    {
        if ($group) {
	    $this->checkIn($group);
	}

	return redirect()->route('admin.users.groups.index', $request->query());
    }

    /**
     * Update the specified group.
     *
     * @param  \App\Http\Requests\Users\Group\UpdateRequest  $request
     * @param  \App\Models\Users\Group $group
     * @return Response
     */
    public function update(UpdateRequest $request, Group $group)
    {
	if (!$group->canEdit()) {
	    return redirect()->route('admin.users.groups.edit', array_merge($request->query(), ['group' => $group->id]))->with('error',  __('messages.generic.edit_not_auth'));
	}

	$group->name = $request->input('name');
	$group->description = $request->input('description');
	$group->updated_by = auth()->user()->id;

	// Ensure the current user has a higher role level than the item owner's or the current user is the item owner.
	if (auth()->user()->getRoleLevel() > $group->role_level || $group->created_by == auth()->user()->id) {
	    $group->created_by = $request->input('created_by');
	    $owner = User::findOrFail($group->created_by);
	    $group->role_level = $owner->getRoleLevel();
	    $group->access_level = $request->input('access_level');
	}

	$group->save();

        if ($request->input('_close', null)) {
	    $this->checkIn($group);
	    // Redirect to the list.
	    return redirect()->route('admin.users.groups.index', $request->query())->with('success', __('messages.groups.update_success'));
	}

	return redirect()->route('admin.users.groups.edit', array_merge($request->query(), ['group' => $group->id]))->with('success', __('messages.groups.update_success'));
    }

    /**
     * Store a new group.
     *
     * @param  \App\Http\Requests\Users\Group\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
	$group = Group::create([
	  'name' => $request->input('name'), 
	  'description' => $request->input('description'), 
	  'access_level' => $request->input('access_level'), 
	  'created_by' => $request->input('created_by')
	]);

	$group->role_level = auth()->user()->getRoleLevel();
	$group->save();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.groups.index', $request->query())->with('success', __('messages.groups.create_success'));
	}

	return redirect()->route('admin.users.groups.edit', array_merge($request->query(), ['group' => $group->id]))->with('success', __('messages.groups.create_success'));
    }

    /**
     * Remove the specified group from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Users\Group $group
     * @return Response
     */
    public function destroy(Request $request, Group $group)
    {
	if (!$group->canDelete()) {
	    return redirect()->route('admin.users.groups.edit', array_merge($request->query(), ['group' => $group->id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	$name = $group->name;

	$group->users()->detach();
	$group->delete();

	return redirect()->route('admin.users.groups.index', $request->query())->with('success', __('messages.groups.delete_success', ['name' => $name]));
    }

    /**
     * Removes one or more groups from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
        $deleted = 0;
        // Remove the groups selected from the list.
        foreach ($request->input('ids') as $id) {
	    $group = Group::findOrFail($id);

	    if (!$group->canDelete()) {
	      return redirect()->route('admin.users.groups.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.delete_not_auth'), 
		      'success' => __('messages.groups.delete_list_success', ['number' => $deleted)])
		  ]);
	    }

	    $group->users()->detach();
	    $group->delete();

	    $deleted++;
	}

	return redirect()->route('admin.users.groups.index', $request->query())->with('success', __('messages.groups.delete_list_success', ['number' => $deleted]));
    }

    /**
     * Checks in one or more groups.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = $this->checkInMultiple($request->input('ids'), '\\App\\Models\\Users\\Group');

	return redirect()->route('admin.users.groups.index', $request->query())->with($messages);
    }
}
