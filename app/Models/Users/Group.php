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

	$query->where('role_level', '<', auth()->user()->getUserRoleLevel())
	      ->orWhereIn('access_level', ['public_ro', 'public_rw'])
	      ->orWhere('created_by', auth()->user()->id);

        return $query->paginate($perPage);
    }

    public function getCreatedByOptions()
    {
	$users = auth()->user()->getAssignableUsers();
	$options = [];

//file_put_contents('debog_file.txt', print_r($users, true));
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
}
