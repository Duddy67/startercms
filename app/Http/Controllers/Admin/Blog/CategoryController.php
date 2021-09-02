<?php

namespace App\Http\Controllers\Admin\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Blog\Category;
use App\Models\Users\User;
use App\Traits\Admin\ItemConfig;
use App\Traits\Admin\CheckInCheckOut;
use App\Http\Requests\Blog\Category\StoreRequest;
use App\Http\Requests\Blog\Category\UpdateRequest;
use Illuminate\Support\Str;


class CategoryController extends Controller
{
    use ItemConfig, CheckInCheckOut;

    /*
     * Instance of the model.
     */
    protected $model;

    /*
     * Name of the model.
     */
    protected $modelName = 'category';

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
        $this->middleware('admin.blog.categories');
	$this->model = new Category;
    }

    /**
     * Show the category list.
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
	$rows = $this->getRowTree($columns, $items);
	$query = $request->query();
	$url = ['route' => 'admin.blog.categories', 'item_name' => 'category', 'query' => $query];

        return view('admin.blog.categories.list', compact('items', 'columns', 'rows', 'actions', 'filters', 'url', 'query'));
    }

    /**
     * Show the form for creating a new category.
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

        return view('admin.blog.categories.form', compact('fields', 'actions', 'query'));
    }

    /**
     * Show the form for editing the specified category.
     *
     * @param  Request  $request
     * @param  int  $id
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function edit(Request $request, $id)
    {
        $category = Category::select('blog_categories.*', 'users.name as owner_name', 'users2.name as modifier_name')
			      ->leftJoin('users', 'blog_categories.owned_by', '=', 'users.id')
			      ->leftJoin('users as users2', 'blog_categories.updated_by', '=', 'users2.id')
			      ->findOrFail($id);

	if (!$category->canAccess()) {
	    return redirect()->route('admin.blog.categories.index')->with('error',  __('messages.generic.access_not_auth'));
	}

	if ($category->checked_out && $category->checked_out != auth()->user()->id) {
	    return redirect()->route('admin.blog.categories.index')->with('error',  __('messages.generic.checked_out'));
	}

	$this->checkOut($category);

        // Gather the needed data to build the form.
	
	$except = (auth()->user()->getRoleLevel() > $category->role_level || $category->owned_by == auth()->user()->id) ? ['owner_name'] : ['owned_by'];

	if ($category->updated_by === null) {
	    array_push($except, 'updated_by', 'updated_at');
	}

        $fields = $this->getFields($category, $except);
	$this->setFieldValues($fields, $category);
	$except = (!$category->canEdit()) ? ['destroy', 'save', 'saveClose'] : [];
        $actions = $this->getActions('form', $except);
	// Add the id parameter to the query.
	$query = array_merge($request->query(), ['category' => $id]);

        return view('admin.blog.categories.form', compact('category', 'fields', 'actions', 'query'));
    }

    /**
     * Checks the record back in.
     *
     * @param  Request  $request
     * @param  \App\Models\Blog\Category  $category (optional)
     * @return Response
     */
    public function cancel(Request $request, Category $category = null)
    {
        if ($category) {
	    $this->checkIn($category);
	}

	return redirect()->route('admin.blog.categories.index', $request->query());
    }

    /**
     * Update the specified category.
     *
     * @param  \App\Http\Requests\Blog\Category\UpdateRequest  $request
     * @param  \App\Models\Blog\Category $category
     * @return Response
     */
    public function update(UpdateRequest $request, Category $category)
    {
	if (!$category->canEdit()) {
	    return redirect()->route('admin.blog.categories.edit', array_merge($request->query(), ['category' => $category->id]))->with('error',  __('messages.generic.edit_not_auth'));
	}

	// Check the selected parent is not a descendant.
	if ($request->input('parent_id')) {
	    $node = Category::findOrFail($request->input('parent_id'));

	    if ($category->id == $request->input('parent_id') || $node->isDescendantOf($category)) {
		return redirect()->route('admin.blog.categories.edit', array_merge($request->query(), ['category' => $category->id]))->with('error',  __('messages.generic.must_not_be_descendant'));
	    }

	    if (!$node->canEdit()) {
		return redirect()->route('admin.blog.categories.edit', array_merge($request->query(), ['category' => $category->id]))->with('error',  __('messages.generic.edit_not_auth'));
	    }
	}

	$category->name = $request->input('name');
	$category->slug = ($request->input('slug')) ? Str::slug($request->input('slug'), '-') : Str::slug($request->input('name'), '-');
	$category->description = $request->input('description');
	$category->updated_by = auth()->user()->id;
	// N.B The nested set model is updated automatically.
	$category->parent_id = $request->input('parent_id');

	// Ensure the current user has a higher role level than the item owner's or the current user is the item owner.
	if (auth()->user()->getRoleLevel() > $category->role_level || $category->owned_by == auth()->user()->id) {
	    $category->owned_by = $request->input('owned_by');
	    $owner = ($category->owned_by == auth()->user()->id) ? auth()->user() : User::findOrFail($category->owned_by);
	    $category->role_level = $owner->getRoleLevel();
	    $category->access_level = $request->input('access_level');
	}

	$category->save();

        if ($request->input('_close', null)) {
	    $this->checkIn($category);
	    // Redirect to the list.
	    return redirect()->route('admin.blog.categories.index', $request->query())->with('success', __('messages.categories.update_success'));
	}

	return redirect()->route('admin.blog.categories.edit', array_merge($request->query(), ['category' => $category->id]))->with('success', __('messages.categories.update_success'));
    }

    /**
     * Store a new category.
     *
     * @param  \App\Http\Requests\Blog\Category\StoreRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request)
    {
	$category = Category::create([
	    'name' => $request->input('name'), 
	    'slug' => ($request->input('slug')) ? Str::slug($request->input('slug'), '-') : Str::slug($request->input('name'), '-'),
	    'status' => $request->input('status'), 
	    'description' => $request->input('description'), 
	    'access_level' => $request->input('access_level'), 
	    'owned_by' => $request->input('owned_by'),
	    'parent_id' => (empty($request->input('parent_id'))) ? null : $request->input('parent_id'),
	]);

	$owner = ($category->owned_by == auth()->user()->id) ? auth()->user() : User::findOrFail($category->owned_by);
	$category->role_level = $owner->getRoleLevel();
	$category->save();

        if ($category->parent_id) {
	    $parent = Category::findOrFail($category->parent_id);
	    $parent->appendNode($category);
	}

        if ($request->input('_close', null)) {
	    return redirect()->route('admin.blog.categories.index', $request->query())->with('success', __('messages.categories.create_success'));
	}

	return redirect()->route('admin.blog.categories.edit', array_merge($request->query(), ['category' => $category->id]))->with('success', __('messages.categories.create_success'));
    }

    /**
     * Remove the specified category from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Blog\Category $category
     * @return Response
     */
    public function destroy(Request $request, Category $category)
    {
	if (!$category->canDelete()) {
	    return redirect()->route('admin.blog.categories.edit', array_merge($request->query(), ['category' => $category->id]))->with('error',  __('messages.generic.delete_not_auth'));
	}

	$name = $category->name;

	//$category->categories()->detach();
	$category->delete();

	return redirect()->route('admin.blog.categories.index', $request->query())->with('success', __('messages.categories.delete_success', ['name' => $name]));
    }

    /**
     * Removes one or more categories from storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massDestroy(Request $request)
    {
        $deleted = 0;
        // Remove the categories selected from the list.
        foreach ($request->input('ids') as $id) {
	    $category = Category::findOrFail($id);

	    if (!$category->canDelete()) {
	      return redirect()->route('admin.blog.categories.index', $request->query())->with(
		  [
		      'error' => __('messages.generic.delete_not_auth'), 
		      'success' => __('messages.categories.delete_list_success', ['number' => $deleted])
		  ]);
	    }

	    //$category->categories()->detach();
	    $category->delete();

	    $deleted++;
	}

	return redirect()->route('admin.blog.categories.index', $request->query())->with('success', __('messages.categories.delete_list_success', ['number' => $deleted]));
    }

    /**
     * Checks in one or more categories.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Response
     */
    public function massCheckIn(Request $request)
    {
        $messages = $this->checkInMultiple($request->input('ids'), '\\App\\Models\\Blog\\Category');

	return redirect()->route('admin.blog.categories.index', $request->query())->with($messages);
    }

    public function massPublish(Request $request)
    {
        foreach ($request->input('ids') as $id) {
	    $category = Category::findOrFail($id);
	    // Cannot published a category if its parent is unpublished.
	    if ($category->parent && $category->parent->status == 'unpublished') {
	        continue;
	    }

	    $category->status = 'published';
	    $category->save();
	}

	return redirect()->route('admin.blog.categories.index', $request->query());
    }

    public function massUnpublish(Request $request)
    {
        foreach ($request->input('ids') as $id) {
	    $category = Category::findOrFail($id);

	    $category->status = 'unpublished';
	    $category->save();

	    // All the descendants must be unpublished as well.
	    foreach ($category->descendants as $descendant) {
	        $descendant->status = 'unpublished';
		$descendant->save();
	    }
	}

	return redirect()->route('admin.blog.categories.index', $request->query());
    }

    /**
     * Reorders a given category a level above.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Blog\Category $category
     * @return Response
     */
    public function up(Request $request, Category $category)
    {
	$category->up();
	return redirect()->route('admin.blog.categories.index', $request->query());
    }

    /**
     * Reorders a given category a level below.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Blog\Category $category
     * @return Response
     */
    public function down(Request $request, Category $category)
    {
	$category->down();
	return redirect()->route('admin.blog.categories.index', $request->query());
    }

    /*
     * Sets field values specific to the Category model.
     *
     * @param  Array of stdClass Objects  $fields
     * @param  \App\Models\Blog\Category $category
     * @return void
     */
    private function setFieldValues(&$fields, $category)
    {
        foreach ($fields as $field) {
            if ($field->name == 'parent_id') {
	        foreach ($field->options as $key => $option) {
		    if ($option['value'] == $category->id) {
		        // Category cannot be its own children.
		        $field->options[$key]['extra'] = ['disabled'];
		    }
		}
	    }
        }
    }
}
