<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;


    public static function getPermissionList($except = [])
    {
	$json = file_get_contents(app_path().'/Models/user/permissions.json', true);

        if ($json === false) {
	   throw new Exception('Load Failed');    
	}

	$list = json_decode($json);

	if (!empty($except)) {
	    foreach ($list as $section => $permissions) {
		foreach ($permissions as $key => $permission) {
		    if (in_array($permission->type, $except)) {
		        unset($list->$section[$key]);
		    }

		    // Remove empty sections.
		    if (empty($list->$section)) {
		        unset($list->$section);
		    }
		}
	    }
	}

	return $list;
    }

    public static function getPermissionArray($except = [])
    {
        $list = self::getPermissionList($except);
	$array = [];

	foreach ($list as $permissions) {
	    foreach ($permissions as $permission) {
	        $array[] = $permission->name;
	    }
	}

	return $array;
    }
}
