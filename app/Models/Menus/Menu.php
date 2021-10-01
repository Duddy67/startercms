<?php

namespace App\Models\Menus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users\Group;
use App\Models\Menus\MenuItem;
use App\Models\Settings\General;
use App\Traits\Admin\AccessLevel;
use App\Traits\Admin\CheckInCheckOut;


class Menu extends Model
{
    use HasFactory, AccessLevel, CheckInCheckOut;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'code',
        'status',
        'owned_by',
        'access_level',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'checked_out_time'
    ];


    /**
     * The groups that belong to the menu.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * Delete the model from the database (override).
     *
     * @return bool|null
     *
     * @throws \LogicException
     */
    public function delete()
    {
        $this->groups()->detach();

        parent::delete();
    }

    /*
     * Gets the menus according to the filter settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);
        $ownedBy = $request->input('owned_by', null);
        $groups = $request->input('groups', []);

	$query = Menu::query();
	$query->select('menus.*', 'users.name as user_name')->leftJoin('users', 'menus.owned_by', '=', 'users.id');
	// Join the role tables to get the owner's role level.
	$query->join('model_has_roles', 'menus.owned_by', '=', 'model_id')->join('roles', 'roles.id', '=', 'role_id');

	if ($search !== null) {
	    $query->where('menus.title', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

	if ($ownedBy !== null) {
	    $query->whereIn('menus.owned_by', $ownedBy);
	}

	if (!empty($groups)) {
	    $query->whereHas('groups', function($query) use($groups) {
		$query->whereIn('id', $groups);
	    });
	}

	$query->where(function($query) {
	    $query->where('roles.role_level', '<', auth()->user()->getRoleLevel())
		  ->orWhereIn('menus.access_level', ['public_ro', 'public_rw'])
		  ->orWhere('menus.owned_by', auth()->user()->id);
	});

        $groupIds = auth()->user()->getGroupIds();

	if(!empty($groupIds)) {
	    $query->orWhereHas('groups', function ($query)  use ($groupIds) {
		$query->whereIn('id', $groupIds);
	    });
	}

        return $query->paginate($perPage);
    }

    private function getMenuItemChildren($menuItems, $item, $node)
    {
        // Loop through the existing menu items.
	foreach ($menuItems as $key => $menuItem) {
	    if ($menuItem->id == $node->parent_id) {
		$menuItems[$key]->children[] = $item;
	    }
	    // Search for sub-children.
	    elseif (!empty($menuItems[$key]->children)) {
	        // Recursive call.
	        $this->getMenuItemChildren($menuItems[$key]->children, $item, $node);
	    }
	}

        return $menuItems;
    }

    public function getMenuItems()
    {
	$nodes = MenuItem::where('menu_code', $this->code)->get()->toTree();
	$menuItems = [];

	$traverse = function ($nodes, $level = 0) use (&$traverse, &$menuItems) {

	    foreach ($nodes as $node) {
		/*if ($this->access_level == 'private' && $item->access_level == 'private') {
		      // Only the menu item's owner can access it.
		      //$extra = ($menuItem->owned_by == auth()->user()->id) ? [] : ['disabled'];
	          }*/

	        $item = new \stdClass();
		$item->id = $node->id;
		$item->title = $node->title;
		$item->url = $node->url;
		$item->level = $level;
		$item->parent_id = $node->parent_id;
		$item->children = [];

		$parent = MenuItem::findOrFail($node->parent_id);

		if ($parent->menu_code != 'root') {
		    $menuItems = $this->getMenuItemChildren($menuItems, $item, $node);
		}
		else {
		    $menuItems[] = $item;
		}

		$traverse($node->children, $level + 1);
	    }
	};

	$traverse($nodes);

	return $menuItems;
    }

    public function getOwnedByOptions()
    {
	$users = auth()->user()->getAssignableUsers();
	$options = [];

	foreach ($users as $user) {
	    $options[] = ['value' => $user->id, 'text' => $user->name];
	}

	return $options;
    }

    public function getStatusOptions()
    {
	return [
	    ['value' => 'published', 'text' => __('labels.generic.published')],
	    ['value' => 'unpublished', 'text' => __('labels.generic.unpublished')],
	];
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        if ($fieldName == 'groups') {
	    return $this->groups->pluck('id')->toArray();
	}

	return $this->{$fieldName};
    }

    public static function getMenus()
    {
        return Menu::all();
    }

    public static function getMenu($code)
    {
        return Menu::where('code', $code)->first();
    }

    public static function makeCodeUnique($code)
    {
        $counter = 1;
	$original = $code;

        while (Menu::where('code', $code)->first()) {
	    $code = $original.'-'.$counter;
	    $counter++;
	}

	return $code;
    }
}
