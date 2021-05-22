<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;


    public static function getReservedRoles()
    {
        return [
	    'superadmin',
	    'admin',
	    'manager',
	    'registered'
	];
    }

    /*
     * Permissions that can be given to an admin user type by the superadmin.
     * However, an admin user type cannot give these permissions to another user (ie: to a manager user type).
     * Therefore, an admin user type cannot create another admin user type.
     */
    public static function getPrivatePermissions()
    {
        return [
	    'global-settings', 
	    'create-role',
	    'update-role',
	    'delete-role',
	    'create-permission',
	    'update-permission',
	    'delete-permission',
	    'update-user',
	    'delete-user'
	];
    }

    /*
     * Permissions that can be given to a manager user type by an admin user type.
     * However, a manager user type cannot give these permissions to another user (ie: to a registered user type).
     * Therefore, a manager user type cannot create another manager user type.
     */
    public static function getProtectedPermissions()
    {
        return [
	    'create-user',
	    'update-own-user',
	    'delete-own-user',
	];
    }

    /*
     * Permissions that can be given to a registered user type by an manager user type.
     */
    public static function getPublicPermissions()
    {
        return [
	    'create-post',
	    'update-post',
	    'delete-post',
	    'update-own-post',
	    'delete-own-post',
	];
    }

    public static function getReservedPermissions()
    {
	return array_merge(self::getPrivatePermissions(), self::getProtectedPermissions(), self::getPublicPermissions()); 
    }

    public static function getPermissionPatterns()
    {
        return [
	    'create-[0-9-a-z\-]+',
	    'update-[0-9-a-z\-]+',
	    'delete-[0-9-a-z\-]+',
	    'update-own-[0-9-a-z\-]+',
	    'delete-own-[0-9-a-z\-]+',
	    'settings-[0-9-a-z\-]+'
	];
    }
}
