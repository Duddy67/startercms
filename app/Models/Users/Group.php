<?php

namespace App\Models\Users;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Users\User;

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

	$query = Group::query();

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

        return $query->paginate($perPage);
    }

}
