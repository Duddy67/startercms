<?php

namespace App\Models\Settings;
use Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


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
	  ['value' => 'private', 'text' => 'Private'],
	  ['value' => 'public_ro', 'text' => 'Public read only'],
	  ['value' => 'public_rw', 'text' => 'Public read write'],
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
