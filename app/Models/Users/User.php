<?php

namespace App\Models\Users;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Admin\RolesPermissions;
use Spatie\Permission\Models\Role;
use App\Models\Users\Group;
use App\Models\Document;
use App\Models\Settings\General;


class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, RolesPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    /**
     * The groups that belong to the user.
     */
    public function groups()
    {
        return $this->belongsToMany(Group::class);
    }

    /**
     * The user's documents.
     */
    public function documents()
    {
        return $this->HasMany(Document::class, 'item_id');
    }

    public function delete()
    {
        Document::deleteRelatedFiles($this);

        $this->documents()->delete();

        parent::delete();
    }

    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $sortedBy = $request->input('sorted_by', null);
        $roles = $request->input('roles', []);
        $groups = $request->input('groups', []);
        $search = $request->input('search', null);

	$query = User::whereHas('roles', function($query) use($roles) {
	    if (!empty($roles)) {
	        $query->whereIn('name', $roles);
	    }
	});

	if (!empty($groups)) {
	    $query->whereHas('groups', function($query) use($groups) {
		$query->whereIn('id', $groups);
	    });
	}

	if ($search !== null) {
	    $query->where('name', 'like', '%'.$search.'%');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

        return $query->paginate($perPage);
    }

    public function getRoleOptions($user = null)
    {
	$roles = auth()->user()->getAssignableRoles($user);
	$options = [];

	foreach ($roles as $role) {
	    $options[] = ['value' => $role->name, 'text' => $role->name];
	}

	return $options;
    }

    public function getGroupsOptions($user = null)
    {
        $groups = Group::all();
	$options = [];

	foreach ($groups as $group) {
	    $options[] = ['value' => $group->id, 'text' => $group->name];
	}

	return $options;
    }

    /*
     * Used with filters.
     */
    public function getRolesOptions()
    {
        $roles = Role::all()->pluck('name')->toArray();

	foreach ($roles as $role) {
	    $options[] = ['value' => $role, 'text' => $role];
	}

	return $options;
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        if ($fieldName == 'role') {
	    return $this->getRoleName();
	}

        if ($fieldName == 'groups') {
	    return $this->groups->pluck('id')->toArray();
	}

	return null;
    }

    public function canUpdate($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	$hierarchy = $this->getRoleHierarchy();
        // Users can only update users lower in the hierarchy.
	if ($hierarchy[$this->getUserRoleType()] > $hierarchy[$this->getUserRoleType($user)]) {
	    return true;
	}

	return false;
    }

    public function canDelete($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	// Users cannot delete their own account.
        if ($this->id == $user->id) {
	    return false;
	}

	$hierarchy = $this->getRoleHierarchy();
        // Users can only delete users lower in the hierarchy.
	if ($hierarchy[$this->getUserRoleType()] > $hierarchy[$this->getUserRoleType($user)]) {
	    return true;
	}

	return false;
    }

    /*
     * Returns the user's role name.
     */
    public function getRoleName()
    {
        return $this->getRoleNames()->toArray()[0];
    }

    /*
     * Blade directive
     */
    public function isAllowedTo($permission)
    {
	return $this->hasRole('super-admin') || $this->hasPermissionTo($permission);
    }

    /*
     * Blade directive
     */
    public function canAccessAdmin()
    {
        return in_array($this->getUserRoleType(), ['super-admin', 'admin', 'manager', 'assistant']);
    }
}
