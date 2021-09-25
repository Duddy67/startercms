<?php

namespace App\Models\Menus;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users\Group;
use App\Traits\Admin\AccessLevel;
use App\Traits\Admin\CheckInCheckOut;


class Menu extends Model
{
    use HasFactory, AccessLevel, CheckInCheckOut;

    /*
     * Gets the menus according to the filter settings.
     */
    public function getItems($request)
    {
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

	return $query->get();
        return $query->paginate($perPage);
    }
}
