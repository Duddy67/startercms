<?php

namespace App\Models\Menus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\General;
use App\Models\Menus\Menu;
use Kalnoy\Nestedset\NodeTrait;
use App\Models\Users\Group;
use App\Traits\Admin\TreeAccessLevel;
use App\Traits\Admin\CheckInCheckOut;


class MenuItem extends Model
{
    use HasFactory, NodeTrait, TreeAccessLevel, CheckInCheckOut;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'url',
        'status',
        'owned_by',
        'access_level',
        'parent_id',
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
     * The groups that belong to the menu item.
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

    /**
     * The group ids the menu item is in.
     *
     * @return array
     */
    public function getGroupIds()
    {
        return $this->groups()->pluck('groups.id')->toArray();
    }

    /*
     * Gets the menu items as a tree.
     */
    public function getItems($request, $code)
    {
        $search = $request->input('search', null);

	if ($search !== null) {
	    return MenuItem::where('title', 'like', '%'.$search.'%')->get();
	}
	else {
	  return MenuItem::select('menu_items.*', 'users.name as user_name')->leftJoin('users', 'menu_items.owned_by', '=', 'users.id')
								            ->where('menu_code', $code)->defaultOrder()->get()->toTree();
	}
    }

    public function getStatusOptions()
    {
	return [
	    ['value' => 'published', 'text' => __('labels.generic.published')],
	    ['value' => 'unpublished', 'text' => __('labels.generic.unpublished')],
	];
    }

    public function getParentIdOptions()
    {
	$nodes = MenuItem::get()->toTree();
	$options = [];
	// Defines the state of the current instance.
	$isNew = ($this->id) ? false : true;

	$traverse = function ($menuItems, $prefix = '-') use (&$traverse, &$options, $isNew) {

	    foreach ($menuItems as $menuItem) {
	        if (!$isNew && $this->access_level != 'private') {
		    // A non private menu item cannot be a private menu item's children.
		    $extra = ($menuItem->access_level == 'private') ? ['disabled'] : [];
		}
		elseif (!$isNew && $this->access_level == 'private' && $menuItem->access_level == 'private') {
		      // Only the menu item's owner can access it.
		      $extra = ($menuItem->owned_by == auth()->user()->id) ? [] : ['disabled'];
		}
		elseif ($isNew && $menuItem->access_level == 'private') {
		      // Only the menu item's owner can access it.
		      $extra = ($menuItem->owned_by == auth()->user()->id) ? [] : ['disabled'];
		}
		else {
		    $extra = [];
		}

		$options[] = ['value' => $menuItem->id, 'text' => $prefix.' '.$menuItem->title, 'extra' => $extra];

		$traverse($menuItem->children, $prefix.'-');
	    }
	};

	$traverse($nodes);

	return $options;
    }

    public function getOwnedByOptions()
    {
	$users = auth()->user()->getAssignableUsers(['assistant', 'registered']);
	$options = [];

	foreach ($users as $user) {
	    $options[] = ['value' => $user->id, 'text' => $user->name];
	}

	return $options;
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
}
