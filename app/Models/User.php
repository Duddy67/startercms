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

    public static $roleTypes = [
        'super-admin',
        'admin',
        'manager',
        'registered'
    ];

    public static $roleValues = [
	'registered' => 1, 
	'assistant' => 2, 
	'manager' => 3, 
	'admin' => 4, 
	'super-admin' => 5
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
        $roleType = self::getRoleType();

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

    public static function getRoleType($user = null)
    {
        // Get the given user or the current user.
        $user = ($user) ? $user : auth()->user();

        $roleName = $user->getRoleNames()->toArray()[0];

	if (in_array($roleName, User::$roleTypes)) {
	    return $roleName;
	}

	$role = Role::findByName($roleName);

	if ($role->hasPermissionTo('create-permission') || $role->hasPermissionTo('create-role')) {
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

    public static function canUpdate($user)
    {
        if (is_int($user)) {
	    $user = User::findOrFail($user);
	}

	if (User::$roleValues[self::getRoleType()] > User::$roleValues[self::getRoleType($user)]) {
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

	if (User::$roleValues[self::getRoleType()] > User::$roleValues[self::getRoleType($user)]) {
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
	return $this->hasPermissionTo($permission) || $this->hasRole('super-admin');
    }

    /*
     * Blade directive
     */
    public function canAccessAdmin()
    {
        return in_array(self::getRoleType(), ['super-admin', 'admin', 'manager', 'assistant']);
    }

    /*
     * Roles that cannot be deleted nor updated.
     */
    public static function getReservedRoles()
    {
        return [
	    'super-admin',
	    'admin',
	    'manager',
	    'assistant',
	    'registered'
	];
    }

    public static function getReservedRoleIds()
    {
        return [1,2,3,4,5];
    }

    /*
     * Permissions that can be given to an admin user type by the superadmin.
     * However, an admin user type cannot give these permissions to another user.
     * (ie: An admin user type cannot create another admin user type.)
     */
    public static function getPrivatePermissions()
    {
        return [
	    'create-permission',
	    'update-permission',
	    'delete-permission',
	    'create-role',
	    'update-role',
	    'delete-role',
	    'update-user',
	    'delete-user',
	    'global-settings', 
	    'blog-settings', 
	];
    }

    /*
     * Permissions that can be given to a manager user type by an admin user type.
     * However, a manager user type cannot give these permissions to another user.
     * (ie: A manager user type cannot create another manager user type.)
     */
    public static function getProtectedPermissions()
    {
        return [
	    'create-user',
	    'update-own-user',
	    'delete-own-user',
	    'create-user-group',
	    'update-user-group',
	    'delete-user-group',
	    'update-post',
	    'delete-post',
	    'create-blog-category',
	    'update-blog-category',
	    'delete-blog-category',
	    'update-own-blog-category',
	    'delete-own-blog-category',
	    'access-admin',
	];
    }

    /*
     * Permissions that can be given to a registered user type by an manager user type.
     */
    public static function getPublicPermissions()
    {
        return [
	    'create-post',
	    'update-own-post',
	    'delete-own-post',
	];
    }

    /*
     * Permissions that cannot be deleted nor updated.
     */
    public static function getReservedPermissions()
    {
	return array_merge(self::getPrivatePermissions(), self::getProtectedPermissions(), self::getPublicPermissions()); 
    }

    public static function getReservedPermissionIds()
    {
        return [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18];
    }

    public static function getPermissionPatterns()
    {
        return [
	    'create-[0-9-a-z\-]+',
	    'update-[0-9-a-z\-]+',
	    'delete-[0-9-a-z\-]+',
	    'update-own-[0-9-a-z\-]+',
	    'delete-own-[0-9-a-z\-]+',
	    '[0-9-a-z\-]+-settings'
	];
    }
}
