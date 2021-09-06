<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Blog\Post;
use App\Models\Users\User;
use App\Models\Users\Group;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Http\Requests\Blog\Post\StoreRequest;
use App\Http\Requests\Blog\Post\UpdateRequest;
use Illuminate\Support\Str;


class PostController extends Controller
{
    use ItemConfig, CheckInCheckOut;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'post';

    /*
     * Name of the plugin.
     */
    protected $pluginName = 'blog';


    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin.blog.posts');
	$this->model = new Post;
    }

    /**
     * Show the post list.
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
	$url = ['route' => 'admin.blog.posts', 'item_name' => 'post', 'query' => $query];

        return view('admin.blog.posts.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new post.
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

        return view('admin.blog.posts.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified post.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $post = Post::select('posts.*', 'users.name as owner_name', 'users2.name as modifier_name')
			->leftJoin('users', 'posts.owned_by', '=', 'users.id')
			->leftJoin('users as users2', 'posts.updated_by', '=', 'users2.id')
			->findOrFail($id);

	if (!$post->canAccess()) {
	    return redirect()->route('admin.blog.posts.index')->with('error',  __('messages.generic.access_not_auth'));
	}

	if ($post->checked_out && $post->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.blog.posts.index')->with('error',  __('messages.generic.checked_out'));
	}

	$this->checkOut($post);

        // Gather the needed data to build the form.
	
	$except = (auth()->user()->getRoleLevel() > $post->role_level || $post->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];

	if ($post->updated_by === null) {
	    array_push($except, 'updated_by', 'updated_at');
	}

        $fields = $this->getFields($post, $except);
	$except = (!$post->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        $actions = $this->getActions('form', $except);
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['post' => $id]);

        return view('admin.blog.posts.form', compact('post', 'fields', 'actions', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Blog\Post  $post (optional)
     * @return Response
     */
    public function cancel(Request $request, Post $post = null)
    {
        if ($post) {
	    $this->checkIn($post);
	}

	return redirect()->route('admin.blog.posts.index', $request->query());
    }

    /**
     * Update the specified post.
     *
     * @param  \App\Http\Requests\Blog\Post\UpdateRequest  $request
     * @param  \App\Models\Blog\Post $post
     * @return Response
     */
    public function update(UpdateRequest $request, Post $post)
    {
	if (!$post->canEdit()) {
	    return redirect()->route('admin.blog.posts.edit', array_merge($request->query(), ['post' => $post->id]))->with('error',  __('messages.generic.edit_not_auth'));
	}

	$post->title = $request->input('title');
	$post->slug = ($request->input('slug')) ? Str::slug($request->input('slug'), '-') : Str::slug($request->input('title'), '-');
	$post->content = $request->input('content');
	$post->updated_by = auth()->user()->id;

	// Ensure the current user has a higher role level than the item owner's or the current user is the item owner.
	if (auth()->user()->getRoleLevel() > $post->role_level || $post->owned_by == auth()->user()->id) {
	    $post->owned_by = $request->input('owned_by');
	    $owner = User::findOrFail($post->owned_by);
	    $post->role_level = $owner->getRoleLevel();
	    $post->access_level = $request->input('access_level');
	}

	$groups = array_merge($request->input('groups', []), Group::getPrivateGroups($post));

	if (!empty($groups)) {
	    $post->groups()->sync($groups);
	}
	else {
	    // Remove all groups for this post.
	    $post->groups()->sync([]);
	}

	$post->save();

        if ($request->input('_close', null)) {
	    $this->checkIn($post);
	    // Redirect to the list.
	    return redirect()->route('admin.blog.posts.index', $request->query())->with('success', __('messages.posts.update_success'));
	}

	return redirect()->route('admin.blog.posts.edit', array_merge($request->query(), ['post' => $post->id]))->with('success', __('messages.posts.update_success'));
    }

    /**
     * Store a new post.
     *
     * @param  \App\Http\Requests\Blog\Post\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
	$post = Post::create([
	  'title' => $request->input('title'), 
	  'slug' => ($request->input('slug')) ? Str::slug($request->input('slug'), '-') : Str::slug($request->input('title'), '-'),
	  'status' => $request->input('status'), 
	  'content' => $request->input('content'), 
	  'access_level' => $request->input('access_level'), 
	  'owned_by' => $request->input('owned_by'),
	]);

	$owner = ($post->owned_by == auth()->user()->id) ? auth()->user() : User::findOrFail($post->owned_by);
	$post->role_level = $owner->getRoleLevel();

	if ($request->input('groups') !== null) {
	    $post->groups()->attach($request->input('groups'));
	}

	$post->save();

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.blog.posts.index', $request->query())->with('success', __('messages.posts.create_success'));
	}

	return redirect()->route('admin.blog.posts.edit', array_merge($request->query(), ['post' => $post->id]))->with('success', __('messages.posts.create_success'));
    }

    /**
     * Remove the specified post from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Blog\Post $post
     * @return Response
     */
    public function destroy(Request $request, Post $post)
    {
	if (!$post->canDelete()) {
	    return redirect()->route('admin.blog.posts.edit', array_merge($request->query(), ['post' => $post->id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	$name = $post->name;
	$post->delete();

	return redirect()->route('admin.blog.posts.index', $request->query())->with('success', __('messages.posts.delete_success', ['name' => $name]));
    }

    /**
     * Removes one or more posts from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
        $deleted = 0;

        // Remove the posts selected from the list.
        foreach ($request->input('ids') as $id) {
	    $post = Post::findOrFail($id);

	    if (!$post->canDelete()) {
	      return redirect()->route('admin.blog.posts.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.delete_not_auth'), 
		      'success' => __('messages.posts.delete_list_success', ['number' => $deleted])
		  ]);
	    }

	    $post->delete();

	    $deleted++;
	}

	return redirect()->route('admin.blog.posts.index', $request->query())->with('success', __('messages.posts.delete_list_success', ['number' => $deleted]));
    }

    /**
     * Checks in one or more posts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = $this->checkInMultiple($request->input('ids'), '\\App\\Models\\Blog\\Post');

	return redirect()->route('admin.blog.posts.index', $request->query())->with($messages);
    }

    /**
     * Publishes one or more posts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massPublish(Request $request)
    {
        $published = 0;

        foreach ($request->input('ids') as $id) {
	    $post = Post::findOrFail($id);

	    if (!$post->canChangeStatus()) {
	      return redirect()->route('admin.blog.posts.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.mass_publish_not_auth'), 
		      'success' => __('messages.posts.publish_list_success', ['number' => $published])
		  ]);
	    }

	    $post->status = 'published';

	    $post->save();

	    $published++;
	}

	return redirect()->route('admin.blog.posts.index', $request->query())->with('success', __('messages.posts.publish_list_success', ['number' => $published]));
    }

    /**
     * Unpublishes one or more posts.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massUnpublish(Request $request)
    {
        $unpublished = 0;

        foreach ($request->input('ids') as $id) {
	    $post = Post::findOrFail($id);

	    if (!$post->canChangeStatus()) {
	      return redirect()->route('admin.blog.posts.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.mass_unpublish_not_auth'), 
		      'success' => __('messages.posts.unpublish_list_success', ['number' => $unpublished])
		  ]);
	    }

	    $post->status = 'unpublished';

	    $post->save();

	    $unpublished++;
	}

	return redirect()->route('admin.blog.posts.index', $request->query())->with('success', __('messages.posts.unpublish_list_success', ['number' => $unpublished]));
    }

    /*
     * Sets field values specific to the Post model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Blog\Post $post
     * @return void
     */
    private function setFieldValues(&$fields, $post)
    {
        // code...
    }
}
