<?php

namespace App\Models\Blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'blog_settings';

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
        $results = Setting::all()->toArray();
	$data = [];

	foreach ($results as $param) {
	    if (!isset($data[$param['group']])) {
		$data[$param['group']] = [];
	    }

	    $data[$param['group']][$param['key']] = $param['value'];
	}

	return $data;
    }

    public static function getPostOrderingOptions()
    {
      return [
	  ['value' => 'no_ordering', 'text' => __('labels.generic.no_ordering')],
	  ['value' => 'title_asc', 'text' => __('labels.generic.title_asc')],
	  ['value' => 'title_desc', 'text' => __('labels.generic.title_desc')],
	  ['value' => 'created_at_asc', 'text' => __('labels.generic.created_at_asc')],
	  ['value' => 'created_at_desc', 'text' => __('labels.generic.created_at_desc')],
	  ['value' => 'updated_at_asc', 'text' => __('labels.generic.updated_at_asc')],
	  ['value' => 'updated_at_desc', 'text' => __('labels.generic.updated_at_desc')],
	  //['value' => 'ordering_asc', 'text' => __('labels.generic.ordering_asc')],
	  //['value' => 'ordering_desc', 'text' => __('labels.generic.ordering_desc')],
      ];
    }
}
