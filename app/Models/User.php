<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\Admin\RolesPermissions;
use App\Models\UserGroup;

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
        return $this->belongsToMany(UserGroup::class);
    }

    public function getItems($request)
    {
        $perPage = $request->input('per_page', 5);
        return User::paginate($perPage);
    }

    public static function getRoleOptions($user = null)
    {
	$roles = auth()->user()->getAssignableRoles($user);
	$options = [];

	foreach ($roles as $role) {
	    $options[] = ['value' => $role->name, 'text' => $role->name];
	}

	return $options;
    }

    /*
     * Used to get the option role value.
     */
    public function getRoleValue()
    {
        return $this->getRoleName();
    }

    public static function getGroupsOptions($user = null)
    {
        $groups = UserGroup::all();
	$options = [];

	foreach ($groups as $group) {
	    $options[] = ['value' => $group->id, 'text' => $group->name];
	}

	return $options;
    }

    /*
     * Used to get the option groups value.
     */
    public function getGroupsValue()
    {
        return $this->groups->pluck('id')->toArray();
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
