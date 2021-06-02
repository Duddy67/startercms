<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;
use App\Models\UserGroup;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

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

    public function getItems()
    {
        return User::all();
    }

    public static function getRoleOptions($user = null)
    {
        $roleType = self::getUserRoleType();

	// Check first if the user is editing their own user account.
	if ($user && auth()->user()->id == $user->id) {
	    // Only display the user role.
	    $roles = Role::where('name', $user->getRoleNames()->toArray()[0])->get();
	}
	// Move on to the role types.
	elseif ($roleType == 'registered') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->whereNotIn('name', ['create-user', 'create-permission', 'create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	elseif ($roleType == 'manager') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->where('name', 'create-user')->whereNotIn('name', ['create-permission', 'create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	elseif ($roleType == 'admin') {
	  $roles = Role::whereDoesntHave('permissions', function ($query) {
	      $query->whereIn('name', ['create-permission', 'create-role']);
	  })->where('name', '!=', 'super-admin')->get();
	}
	// super-admin
	else {
	    $roles = Role::whereNotIn('name', ['super-admin'])->get();
	}

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

    public static function getUserRoleType($user = null)
    {
        // Get the given user or the current user.
        $user = ($user) ? $user : auth()->user();
        $roleName = $user->getRoleNames()->toArray()[0];

	if ($roleName == 'super-admin') {
	    return 'super-admin';
	}

	return self::getRoleType($roleName);

    }

    public static function getRoleType($role)
    {
	$role = (is_string($role)) ? Role::findByName($role) : $role;

	if ($role->hasPermissionTo('create-role')) {
	    return 'admin';
	}
	elseif ($role->hasPermissionTo('create-user')) {
	    return 'manager';
	}
	elseif ($role->hasPermissionTo('access-admin')) {
	    return 'assistant';
	}
	else {
	    return 'registered';
	}
    }

    public static function getRoleHierarchy()
    {
	return [
	    'registered' => 1, 
	    'assistant' => 2, 
	    'manager' => 3, 
	    'admin' => 4, 
	    'super-admin' => 5
	];
    }

    public static function canUpdate($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	$hierarchy = self::getRoleHierarchy();

	if ($hierarchy[self::getUserRoleType()] > $hierarchy[self::getUserRoleType($user)]) {
	    return true;
	}

	return false;
    }

    public static function canDelete($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	// Users cannot delete their own account.
        if (auth()->user()->id == $user->id) {
	    return false;
	}

	$hierarchy = self::getRoleHierarchy();

	if ($hierarchy[self::getUserRoleType()] > $hierarchy[self::getUserRoleType($user)]) {
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
        return in_array(self::getUserRoleType(), ['super-admin', 'admin', 'manager', 'assistant']);
    }
}
