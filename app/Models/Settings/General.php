<?php

namespace App\Models\Settings;
use Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Models\Users\Group;
use App\Models\Users\User;


class General extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'settings_general';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'group',
        'key',
        'value',
    ];

    /**
     * No timestamps.
     *
     * @var boolean
     */
    public $timestamps = false;


    public static function getData()
    {
        $results = General::all()->toArray();
	$data = [];

	foreach ($results as $param) {
	    if (!isset($data[$param['group']])) {
		$data[$param['group']] = [];
	    }

	    $data[$param['group']][$param['key']] = $param['value'];
	}

	return $data;
    }

    /*
     * Returns the value of a given key from a given group.
     * @param  string  $group
     * @param  string  $key
     * @return string
     */
    public static function getGeneralValue($group, $key)
    {
        return General::where(['group' => $group, 'key' => $key])->pluck('value')->first();
    }

    public static function getPerPageOptions()
    {
      return [
	  ['value' => 2, 'text' => 2],
	  ['value' => 5, 'text' => 5],
	  ['value' => 10, 'text' => 10],
	  ['value' => 15, 'text' => 15],
	  ['value' => 20, 'text' => 20],
	  ['value' => 25, 'text' => 25],
      ];
    }

    public static function getAccessLevelOptions()
    {
      return [
	  ['value' => 'private', 'text' => __('labels.generic.private')],
	  ['value' => 'public_ro', 'text' => __('labels.generic.public_ro')],
	  ['value' => 'public_rw', 'text' => __('labels.generic.public_rw')],
      ];
    }

    public static function getStatusOptions()
    {
	return [
	    ['value' => 'published', 'text' => __('labels.generic.published')],
	    ['value' => 'unpublished', 'text' => __('labels.generic.unpublished')],
	];
    }

    public static function getSortedByOptions($pluginName, $modelName)
    {
	$json = file_get_contents(app_path().'/Models/'.ucfirst($pluginName).'/'.$modelName.'/columns.json', true);
	$columns = json_decode($json);
	$options = [];

	foreach ($columns as $column) {
	    if (isset($column->extra) && in_array('sortable', $column->extra)) {
	        $options[] = ['value' => $column->name.'_asc', 'text' => $column->name.' asc'];
	        $options[] = ['value' => $column->name.'_desc', 'text' => $column->name.' desc'];
	    }
	}

	return $options;
    }

    /*
     * Builds the options for the 'groups' select field.
     *
     * @return Array
     */
    public static function getGroupsOptions()
    {
        $groups = Group::all();
	$options = [];

	foreach ($groups as $group) {
	    $owner = ($group->owned_by == auth()->user()->id) ? auth()->user() : User::findOrFail($group->owned_by);

	    // Ensure the current user can use this group.
	    if ($group->access_level == 'private' && $owner->getRoleLevel() >= auth()->user()->getRoleLevel() && $group->owned_by != auth()->user()->id) {
		continue;
	    }

	    $options[] = ['value' => $group->id, 'text' => $group->name];
	}

	return $options;
    }

    public static function getOwnedByOptions($table)
    {
	$owners = DB::table($table)->leftJoin('users', $table.'.owned_by', '=', 'users.id')
				   ->join('model_has_roles', $table.'.owned_by', '=', 'model_id')
				   ->join('roles', 'roles.id', '=', 'role_id')
				   ->select(['users.id', 'users.name'])
				   ->whereIn($table.'.access_level', ['public_ro', 'public_rw'])
				   ->orWhere('roles.role_level', '<', auth()->user()->getRoleLevel())
				   ->orWhere($table.'.owned_by', auth()->user()->id)->distinct()->get();
	$options = [];

	foreach ($owners as $owner) {
	    $options[] = ['value' => $owner->id, 'text' => $owner->name];
	}

	return $options;
    }

    public static function getTimezoneOptions()
    {
        $timezoneIdentifiers = \DateTimeZone::listIdentifiers();
	$options = [];

	foreach ($timezoneIdentifiers as $identifier) {
	    $options[] = ['value' => $identifier, 'text' => $identifier];
	}

	return $options;
    }

    /*
     * Generic function that returns model values which are handled by select inputs. 
     */
    public function getSelectedValue($fieldName)
    {
        if ($fieldName == 'per_page') {
	    return $this->where(['group' => 'pagination', 'key' => 'per_page'])->pluck('value')->first();
	}
    }

    public static function getAppSettings()
    {
        $data = DB::table('settings_general')->where('group', 'app')->get();
	$settings = [];

	foreach ($data as $row) {
	    $settings['app.'.$row->key] = $row->value;
	}

	return $settings;
    }
}
