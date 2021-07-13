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

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

        return $query->paginate($perPage);
    }

}
