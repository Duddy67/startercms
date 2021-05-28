<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;


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
	    'create-role',
	    'update-role',
	    'delete-role',
	    'create-permission',
	    'update-permission',
	    'delete-permission',
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
	    'update-post',
	    'delete-post',
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
