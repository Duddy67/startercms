<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\User\User;
use App\Models\User\Group;
use Illuminate\Support\Facades\Hash;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Models\Settings\Email;
use App\Models\Cms\Document;
use App\Http\Requests\User\User\StoreRequest;
use App\Http\Requests\User\User\UpdateRequest;


class UserController extends Controller
{
    use ItemConfig;

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
    protected $pluginName = 'user';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.user.users');
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
        $url = ['route' => 'admin.user.users', 'item_name' => 'user', 'query' => $query];

        return view('admin.user.users.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
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

        return view('admin.user.users.form', compact('fields', 'actions', 'query'));
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
            return redirect()->route('admin.user.users.index', array_merge($request->query(), ['user' => $id]))->with('error', __('messages.user.edit_user_not_auth'));
        }

        if ($user->checked_out && $user->checked_out != auth()->user()->id) {
            return redirect()->route('admin.user.users.index', array_merge($request->query(), ['user' => $id]))->with('error',  __('messages.generic.checked_out'));
        }

        $user->checkOut();

        // Gather the needed data to build the form.
        
        $except = ($user->updated_by === null) ? ['updated_by', 'updated_at'] : [];
        $fields = $this->getFields($user, $except);
        $this->setFieldValues($fields, $user);
        // Users cannot delete their own account.
        $except = (auth()->user()->id == $user->id) ? ['destroy'] : [];
        $actions = $this->getActions('form', $except);
        // Add the id parameter to the query.
        $query = array_merge($request->query(), ['user' => $id]);
        $photo = $user->documents()->where('field', 'photo')->latest('created_at')->first();

        return view('admin.user.users.form', compact('user', 'fields', 'actions', 'query', 'photo'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\User\User $user (optional)
     * @return Response
     */
    public function cancel(Request $request, User $user = null)
    {
        if ($user) {
            $user->checkIn();
        }

        return redirect()->route('admin.user.users.index', $request->query());
    }

    /**
     * Update the specified user.
     *
     * @param  \App\Http\Requests\User\User\UpdateRequest  $request
     * @param  \App\Models\User\User $user
     * @return Response
     */
    public function update(UpdateRequest $request, User $user)
    {
        if (!auth()->user()->canUpdate($user) && auth()->user()->id != $user->id) {
            return redirect()->route('admin.user.users.edit', $user->id)->with('error',  __('messages.user.update_user_not_auth'));
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

        $groups = array_merge($request->input('groups', []), Group::getPrivateGroups($user));

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
            $user->checkIn();
            return redirect()->route('admin.user.users.index', $request->query())->with('success', __('messages.user.update_success'));
        }

        return redirect()->route('admin.user.users.edit', array_merge($request->query(), ['user' => $user->id]))->with('success', __('messages.user.update_success'));
    }

    /**
     * Store a new user.
     *
     * @param  \App\Http\Requests\User\User\StoreRequest  $request
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

        if ($request->input('_close', null)) {
            return redirect()->route('admin.user.users.index', $request->query())->with('success', __('messages.user.create_success'));
        }

        return redirect()->route('admin.user.users.edit', array_merge($request->query(), ['user' => $user->id]))->with('success', __('messages.user.create_success'));
    }

    /**
     * Remove the specified user from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User\User $user
     * @return Response
     */
    public function destroy(Request $request, User $user)
    {
        if (!auth()->user()->canDelete($user)) {
            return redirect()->route('admin.user.users.edit', array_merge($request->query(), ['user' => $user->id]))->with('error', __('messages.user.delete_user_not_auth'));
        }

        if ($dependencies = $user->hasDependencies()) {
            return redirect()->route('admin.user.users.edit', array_merge($request->query(), ['user' => $user->id]))
                             ->with('error', __('messages.user.alert_user_dependencies', ['name' => $user->name, 'number' => $dependencies['nbItems'],
                                    'dependencies' => __('labels.title.'.$dependencies['name'])]));
        }

        $name = $user->name;

        $user->delete();

        return redirect()->route('admin.user.users.index', $request->query())->with('success', __('messages.user.delete_success', ['name' => $name]));
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
            // Counter.
            $deleted = 0;

            // Remove the users selected from the list.
            foreach ($request->input('ids') as $key => $id) {
                $user = User::findOrFail($id);
                // Prepare the message about the users already deleted in case the function has to return an error.
                $messages = ($deleted) ? ['success' => __('messages.user.delete_list_success', ['number' => $deleted])] : [];

                if (!auth()->user()->canDelete($user)) {
                    $messages['error'] = __('messages.user.delete_list_not_auth', ['name' => $user->name]);

                    return redirect()->route('admin.user.users.index', $request->query())->with($messages);
                }

                if ($dependencies = $user->hasDependencies()) {
                    $messages['error'] = __('messages.user.alert_user_dependencies', ['name' => $user->name, 'number' => $dependencies['nbItems'],
                                                                                       'dependencies' => __('labels.title.'.$dependencies['name'])]);

                    return redirect()->route('admin.user.users.index', $request->query())->with($messages);
                }

                $user->delete();

                $deleted++;
            }

            return redirect()->route('admin.user.users.index', $request->query())->with('success', __('messages.user.delete_list_success', ['number' => count($request->input('ids'))]));
        }

        return redirect()->route('admin.user.users.index', $request->query())->with('error', __('messages.generic.no_item_selected'));
    }

    /**
     * Checks in one or more users.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = CheckInCheckOut::checkInMultiple($request->input('ids'), '\\App\\Models\\User\\User');

        return redirect()->route('admin.user.users.index', $request->query())->with($messages);
    }

    /**
     * Show the batch form (into an iframe).
     *
     * @param  Request  $request
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function batch(Request $request)
    {
        $fields = $this->getSpecificFields(['role', 'groups']);
        $actions = $this->getActions('batch');
        $query = $request->query();
        $route = 'admin.user.users';

        return view('admin.share.batch', compact('fields', 'actions', 'query', 'route'));
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

            if ($request->input('groups') !== null) {
                if ($request->input('_selected_groups') == 'add') {
                    $user->groups()->syncWithoutDetaching($request->input('groups'));
                }
                else {
                    // Remove the selected groups from the current groups and get the remaining groups.
                    $groups = array_diff($user->getGroupIds(), $request->input('groups'));
                    $user->groups()->sync($groups);
                }
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

            $updates++;
        }

        if ($updates) {
            $messages['success'] = __('messages.generic.mass_update_success', ['number' => $updates]);
        }

        return redirect()->route('admin.user.users.index')->with($messages);
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
                    $groups = [];

                    $user->groups()->each(function ($group, $key) use(&$groups, $user) {
                        // Check for private groups.
                        if ($group->access_level == 'private' && $group->owned_by != auth()->user()->id && auth()->user()->getRoleLevel() <= $group->getOwnerRoleLevel()) {
                            // Don't show this private group if the item user is not part of it.
                            if (!in_array($group->id, $user->getGroupIds())) {
                                // N.B: same as 'continue' with each().
                                return; 
                            }
                        } 

                        $groups[] = $group->name;
                    });

                    $groups = (!empty($groups)) ? implode(', ', $groups) : '-';
                    $rows[$key]->groups = $groups;
                }
            }
        }
    }

    /*
     * Sets field values specific to the User model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\User\User  $user
     * @return void
     */
    private function setFieldValues(&$fields, $user)
    {
        foreach ($fields as $field) {
            // The current user is editing their own account.
            if ($field->name == 'role' && $user->id == auth()->user()->id) {
                // Add the current user's role to the select list.
                $field->options[] = ['value' => $user->getRoleName(), 'text' => $user->getRoleName()];
            }
        }
    }
}
