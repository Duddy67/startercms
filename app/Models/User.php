<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\Permission\Models\Role;

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

    public $roleTypes = [
        'super-admin',
        'admin',
        'manager',
        'registered'
    ];

    public function getItems()
    {
        return User::all();
    }

    public function getRoleOptions()
    {
        $roleType = $this->getRoleType();

	if ($roleType == 'registered') {
	}
	elseif ($roleType == 'manager') {
	}
	elseif ($roleType == 'admin') {
	}
	// The super-admin is editing their own user account.
	elseif ($this->getRoleNames()->toArray()[0] == 'super-admin') {
	    $roles = Role::where('name', 'super-admin')->get();
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

    public function getRoleType()
    {
        // Get the current user.
        $user = auth()->user();

        $roleName = $user->getRoleNames()->toArray()[0];
//file_put_contents('debog_file.txt', print_r($roles, true));

	if (in_array($roleName, $this->roleTypes)) {
	    return $roleName;
	}

	$role = Role::findByName($roleName);

	if ($role->hasPermissionTo('create-permission') || $role->hasPermissionTo('create-role')) {
	    return 'admin';
	}
	elseif ($role->hasPermissionTo('create-user')) {
	    return 'manager';
	}
	else {
	    return 'registered';
	}
    }

    public function getRoleValue()
    {
        $roles = $this->getRoleNames();
	return $roles[0];
    }
}
