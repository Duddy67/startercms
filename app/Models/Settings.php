<?php

namespace App\Models;
use Request;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

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

    public static function getSortedByOptions($itemName)
    {
	$json = file_get_contents(app_path().'/Models/'.$itemName.'/columns.json', true);
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
}
