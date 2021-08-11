<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users\User;
use App\Models\Settings\General;

class Group extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'created_by',
        'description',
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
     * The users that belong to the group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    /*
     * Gets the group items according to the filter, sort and pagination settings.
     */
    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);

	$query = Group::query();
	$query->select('groups.*', 'users.name as user_name')->leftJoin('users', 'groups.created_by', '=', 'users.id');

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

	$query->where('role_level', '<', auth()->user()->getRoleLevel())
	      ->orWhereIn('access_level', ['public_ro', 'public_rw'])
	      ->orWhere('created_by', auth()->user()->id);

        return $query->paginate($perPage);
    }

    public function getCreatedByOptions()
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
        if ($fieldName == 'created_by') {
	    return $this->created_by;
	}
	elseif ($fieldName == 'access_level') {
	    return $this->access_level;
	}

	return null;
    }

    /*
     * Checks whether the current user is allowed to access a given group according to their role level.
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
     * Checks whether the current user is allowed to edit a given group according to their role level.
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
     * Checks whether the current user is allowed to delete a given group according to their role level.
     *
     * @return boolean
     */
    public function canDelete()
    {
        if (auth()->user()->hasRole('super-admin')) {
	    return true;
	}

	// The owner role level is lower than the current user's or the current user owns the group.
	if ($this->role_level < auth()->user()->getRoleLevel() || $this->created_by == auth()->user()->id) {
	    return true;
	}

	return false;
    }
}
