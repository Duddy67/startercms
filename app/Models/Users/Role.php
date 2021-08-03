<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Models\Role as SpatieRole;
use App\Models\Settings\General;


class Role extends SpatieRole 
{
    use HasFactory;

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
     * Gets the role items according to the filter, sort and pagination settings.
     *
     * @param  Request  $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);

	$query = Role::query();
	$query->select('roles.*', 'users.name as user_name')->leftJoin('users', 'roles.created_by', '=', 'users.id');

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        return $this->{$fieldName};
    }

    /*
     * Checks whether the current user is allowed to access a given role according to their role level.
     *
     * @return boolean
     */
    public function canAccess()
    {
        if ($this->access_level == 'public_ro' || $this->canEdit()) {
	    return true;
	}

	return false;
    }

    /*
     * Checks whether the current user is allowed to edit a given role according to their role level.
     *
     * @return boolean
     */
    public function canEdit()
    {
        if ($this->access_level == 'public_rw' || $this->role_level < auth()->user()->getRoleLevel() || $this->created_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }

    /*
     * Checks whether the current user is allowed to delete a given role according to their role level.
     *
     * @return boolean
     */
    public function canDelete()
    {
        if (auth()->user()->hasRole('super-admin')) {
	    return true;
	}

	// The owner role level is lower than the current user's or the current user owns the role.
	if ($this->role_level < auth()->user()->getRoleLevel() || $this->created_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }
}
