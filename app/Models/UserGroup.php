<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class UserGroup extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
    ];

    /**
     * The users that belong to the group.
     */
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function getItems($request)
    {
        $perPage = $request->input('per_page', 5);
        $search = $request->input('search', null);

	$query = UserGroup::query();

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

}
