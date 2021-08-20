<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Users\User;
use Illuminate\Support\Facades\Hash;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Models\Settings\Email;
use App\Models\Cms\Document;
use App\Http\Requests\Users\User\StoreRequest;
use App\Http\Requests\Users\User\UpdateRequest;


class UserController extends Controller
{
    use ItemConfig, CheckInCheckOut;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'user';

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
        $this->middleware('admin.users.users');
	$this->model = new User;
    }

    /**
     * Show the user list.
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
	$rows = $this->getRows($columns, $items, ['roles']);
	$this->setRowValues($rows, $columns, $items);
	$query = $request->query();
	$url = ['route' => 'admin.users.users', 'item_name' => 'user', 'query' => $query];

        return view('admin.users.users.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new user.
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function create(Request $request)
    {
        // Gather the needed data to build the form.
        $fields = $this->getFields(null, ['updated_by']);
        $actions = $this->getActions('form', ['destroy']);
	$query = $request->query();

        return view('admin.users.users.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified user.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $user = User::select('users.*', 'users2.name as modifier_name')->leftJoin('users as users2', 'users.updated_by', '=', 'users2.id')->findOrFail($id);

	if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
	    return redirect()->route('admin.users.users.index')->with('error', __('messages.users.edit_user_not_auth'));
	}

	if ($user->checked_out && $user->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.users.users.index')->with('error',  __('messages.generic.checked_out'));
	}

	$this->checkOut($user);

        // Gather the needed data to build the form.
	
	$except = ($user->updated_by === null) ? ['updated_by', 'updated_at'] : [];
        $fields = $this->getFields($user, $except);
	// Users cannot delete their own account.
	$except = (auth()->user()->id == $user->id) ? ['destroy'] : [];
        $actions = $this->getActions('form', $except);
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['user' => $id]);
	$photo = $user->documents()->where('field', 'photo')->latest('created_at')->first();

        return view('admin.users.users.form', compact('user', 'fields', 'actions', 'query', 'photo'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Users\User $user (optional)
     * @return Response
     */
    public function cancel(Request $request, User $user = null)
    {
        if ($user) {
	    $this->checkIn($user);
	}

	return redirect()->route('admin.users.users.index', $request->query());
    }

    /**
     * Update the specified user.
     *
     * @param  \App\Http\Requests\Users\User\UpdateRequest  $request
     * @param  \App\Models\Users\User $user
     * @return Response
     */
    public function update(UpdateRequest $request, User $user)
    {
	if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
	    return redirect()->route('admin.users.users.edit', $user->id)->with('error',  __('messages.users.update_user_not_auth'));
	}

	$user->name = $request->input('name');
	$user->email = $request->input('email');
	$user->updated_by = auth()->user()->id;

	if ($request->input('password') !== null) {
	    $user->password = Hash::make($request->input('password'));
	}

	// Users cannot modify their own role and they cannot select or deselect a private role.
	if (auth()->user()->id != $user->id && !$user->isRolePrivate()) {
	    $user->syncRoles($request->input('role'));
	}

	$groups = array_merge($request->input('groups', []), $user->getPrivateGroups());

	if (!empty($groups)) {
	    $user->groups()->sync($groups);
	}
	else {
	    // Remove all groups for this user.
	    $user->groups()->sync([]);
	}

	$user->save();

	if ($document = $this->uploadPhoto($request)) {
	    $user->documents()->save($document);
	}

        if ($request->input('_close', null)) {
	    $this->checkIn($user);
	    return redirect()->route('admin.users.users.index', $request->query())->with('success', __('messages.users.update_success'));
	}

	return redirect()->route('admin.users.users.edit', array_merge($request->query(), ['user' => $user->id]))->with('success', __('messages.users.update_success'));
    }

    /**
     * Store a new user.
     *
     * @param  \App\Http\Requests\Users\User\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
	$user = User::create([
	    'name' => $request->input('name'),
	    'email' => $request->input('email'),
	    'password' => Hash::make($request->input('password')),
	]);

	$user->assignRole($request->input('role'));

	if ($request->input('groups') !== null) {
	    $user->groups()->attach($request->input('groups'));
	}

	Email::sendEmail('user_registration', $user);

	if ($document = $this->uploadPhoto($request)) {
	    $user->documents()->save($document);
	}

	$query = $request->query();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.users.users.index', $query)->with('success', __('messages.users.create_success'));
	}

	$query['user'] = $user->id;

	return redirect()->route('admin.users.users.edit', $query)->with('success', __('messages.users.create_success'));
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Users\User $user
     * @return Response
     */
    public function destroy(Request $request, User $user)
    {
	if (!auth()->user()->canDelete($user)) {
	    return redirect()->route('admin.users.users.edit', $user->id)->with('error', __('messages.users.delete_user_not_auth'));
	}

	$name = $user->name;
	$user->delete();

	return redirect()->route('admin.users.users.index', $request->query())->with('success', __('messages.users.delete_success', ['name' => $name]));
    }

    /**
     * Remove one or more users from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
        if ($request->input('ids') !== null) {

	    // Remove the users selected from the list.
	    foreach ($request->input('ids') as $key => $id) {
		$user = User::findOrFail($id);

		// Stop the deletions as soon as the current user is not authorized to delete one of the user in the list.
		if (!auth()->user()->canDelete($user)) {
		    // Informs about the users previously deleted.
		    if ($key > 0) {
			$request->session()->flash('success', __('messages.users.delete_list_success', ['number' => $key]));
		    }

		    return redirect()->route('admin.users.users.index')->with('error', __('messages.users.delete_list_not_auth', ['name' => $user->name]));
		}

		$user->delete();
	    }

	    return redirect()->route('admin.users.users.index', $request->query())->with('success', __('messages.users.delete_list_success', ['number' => count($request->input('ids'))]));
	}

	return redirect()->route('admin.users.users.index', $request->query())->with('error', __('messages.generic.no_item_selected'));
    }

    /**
     * Checks in one or more users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = $this->checkInMultiple($request->input('ids'), '\\App\\Models\\Users\\User');

	return redirect()->route('admin.users.users.index', $request->query())->with($messages);
    }

    /**
     * Show the batch form (in an iframe).
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function batch(Request $request)
    {
        $fields = $this->getFields(null, ['name', 'email', 'photo', 'created_at', 'updated_at', 'password', 'password_confirmation']);
        $actions = $this->getActions('batch');
	$query = $request->query();

        return view('admin.users.users.batch', compact('fields', 'actions', 'query'));
    }

    /**
     * Updates role and groups parameters of one or more users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massUpdate(Request $request)
    {
        $updates = 0;
	$messages = [];

        foreach ($request->input('ids') as $key => $id) {
	    $user = User::findOrFail($id);

	    // Check for authorisation.
	    if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
		$messages['error'] = __('messages.generic.mass_update_not_auth');
		continue;
	    }

	    if (!empty($request->input('role'))) {

		if (auth()->user()->id != $user->id) {
		    $user->syncRoles($request->input('role'));
		}
		// Users cannot modify the role attribute of their own account.
		else {
		    $messages['error'] = __('messages.generic.mass_update_not_auth');
		    continue;
		}
	    }

	    if ($request->input('groups') !== null) {
		$user->groups()->syncWithoutDetaching($request->input('groups'));
	    }

	    $updates++;
	}

	$messages['success'] = __('messages.generic.mass_update_success', ['number' => $updates]);

	return redirect()->route('admin.users.users.index')->with($messages);
    }

    /*
     * Creates a Document associated with the uploaded photo file.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \App\Models\Cms\Document
     */
    private function uploadPhoto($request)
    {
        if ($request->hasFile('photo') && $request->file('photo')->isValid()) {
	    $document = new Document;
	    $document->upload($request->file('photo'), 'user', 'photo');

	    return $document;
	}

	return null;
    }

    /*
     * Sets the row values specific to the User model.
     *
     * @param  Array  $rows
     * @param  Array of stdClass Objects  $columns
     * @param  \Illuminate\Pagination\LengthAwarePaginator  $users
     * @return void
     */
    private function setRowValues(&$rows, $columns, $users)
    {
        foreach ($users as $key => $user) {
	    foreach ($columns as $column) {
	        if ($column->name == 'role') {
		    $roles = $user->getRoleNames();
		    $rows[$key]->role = $roles[0];
		}

	        if ($column->name == 'groups') {
		    $groups = $user->groups()->pluck('name')->toArray();
		    $groups = (!empty($groups)) ? implode(',', $groups) : '-';
		    $rows[$key]->groups = $groups;
		}
	    }
	}
    }

    /*
     * Sets field values specific to the User model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Users\User  $user
     * @return void
     */
    private function setFieldValues(&$fields, $user)
    {
	// Specific operations here...
    }
}
