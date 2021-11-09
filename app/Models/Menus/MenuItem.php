<?php

namespace App\Models\Menus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Settings\General;
use App\Models\Menus\Menu;
use Kalnoy\Nestedset\NodeTrait;
use App\Models\Users\Group;
use App\Traits\Admin\CheckInCheckOut;


class MenuItem extends Model
{
    use HasFactory, NodeTrait, CheckInCheckOut;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'url',
        'status',
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
	  return MenuItem::where('menu_code', $code)->defaultOrder()->get()->toTree();
	}
    }

    public function getParentIdOptions()
    {
	$nodes = MenuItem::get()->toTree();
	$options = [];
	// Defines the state of the current instance.
	$isNew = ($this->id) ? false : true;

	$traverse = function ($menuItems, $prefix = '-') use (&$traverse, &$options, $isNew) {

	    foreach ($menuItems as $menuItem) {
		$options[] = ['value' => $menuItem->id, 'text' => $prefix.' '.$menuItem->title];

		$traverse($menuItem->children, $prefix.'-');
	    }
	};

	$traverse($nodes);

	return $options;
    }

    public function canChangeStatus()
    {
	// Rely on the parent menu for authorisations.
	$menu = Menu::where('code', $this->menu_code)->first();

	return $menu->canChangeStatus();
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
	return $this->{$fieldName};
    }
}
