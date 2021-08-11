<?php

namespace App\Traits\Admin;

use App\Models\Settings\General;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


trait ItemConfig
{
    /*
     * Returns the column data for an item list.
     *
     * @return Array of stdClass Objects
     */  
    public function getColumns()
    {
	$columns = $this->getData('columns');

	// Possible operations here...

	return $columns;
    }

    /*
     * Sets the values for each item row.
     *
     * @param Array of stdClass Objects  $columns
     * @param \Illuminate\Pagination\LengthAwarePaginator  $items
     * @param Array  $except 
     * @return Array of stdClass Objects
     */  
    public function getRows($columns, $items, $except = [])
    {
        $rows = [];

        foreach ($items as $item) {
	    $row = new \stdClass();
	    $row->item_id = $item->id;

	    if ($item->checked_out !== null) {
	        $row->checked_out = DB::table('users')->where('id', $item->checked_out)->pluck('name')->first();

		if (is_string($item->checked_out_time)) {
		    // Converts the string date into Carbon object.
		    $item->checked_out_time = Carbon::parse($item->checked_out_time);
		}

		$row->checked_out_time = $item->checked_out_time->toFormattedDateString();
	    }

	    foreach ($columns as $column) {
	        if (!in_array($column->name, $except)) {

		    if ($column->type == 'date') {
			$row->{$column->name} = $item->{$column->name}->toFormattedDateString();
		    }
		    elseif ($column->name == 'created_by') {
		        $row->created_by = $item->user_name;
		    }
		    elseif ($column->name == 'access_level') {
		        $row->access_level = __('labels.generic.'.$item->access_level);
		    }
		    else {
			$row->{$column->name} = $item->{$column->name};
		    }
		}
		else {
		    $row->{$column->name} = null;
		}
	    }

	    $rows[] = $row;
	}

	return $rows;
    }

    /*
     * Returns the field data for an item form.
     *
     * @param A model instance  $item
     * @return Array of stdClass Objects
     */  
    public function getFields($item = null, $except = [])
    {
	$fields = $this->getData('fields');

	foreach ($fields as $key => $field) {
	    // Remove unwanted fields if any.
	    if (in_array($field->name, $except)) {
	        unset($fields[$key]);
		continue;
	    }

	    // Set the select field types.
	    if ($field->type == 'select') {
	        // Build the function name.
		$function = 'get'.str_replace('_', '', ucwords($field->name, '_')).'Options';

		if ($field->name == 'access_level') {
		    // Common options.
		    $options = General::$function();
		}
		else {
		    $options = $this->model->$function();
		}

		$fields[$key]->options = $options;

		if ($item) {
		    $fields[$key]->value = $item->getSelectedValue($field->name);
		}
	    }
	}

	if ($item) {
	    foreach ($fields as $key => $field) {
		if (isset($field->name)) {
		    // Skip the fields which are already set.
		    if ($field->type == 'select') {
		        continue;
		    }

		    if ($field->type == 'date') {
			$fields[$key]->value = $item->{$field->name}->toDateString();
		    }
		    elseif ($field->name == 'updated_by') {
			$fields[$key]->value = $item->modifier_name;
		    }
		    else {
			$fields[$key]->value = $item->{$field->name};
		    }
		}
		else {
		    $fields[$key]->value = null;
		}
	    }
	}

	return $fields;
    }

    /*
     * Returns the filter data for an item list.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Array of stdClass Objects
     */  
    public function getFilters($request)
    {
	$filters = $this->getData('filters');

	foreach ($filters as $key => $filter) {
	    if ($filter->type == 'button') {
	        continue;
	    }

	    $default = null;

	    if ($filter->type == 'select') {
		// Build the function name.
		$function = 'get'.str_replace('_', '', ucwords($filter->name, '_')).'Options';

		// General filter.
		if ($filter->name == 'per_page') {
		    $options = General::$function();
		    $default = General::getGeneralValue('pagination', 'per_page');
		}
		// General filter.
		elseif ($filter->name == 'sorted_by') {
		    $options = General::$function($this->pluginName, $this->modelName);
		}
		else {
		    $options = $this->model->$function();
		}

		$filters[$key]->options = $options;
	    }

	    $filters[$key]->value = $request->input($filter->name, $default);
	}

	return $filters;
    }

    /*
     * Returns the action data for an item list or form.
     *
     * @param  string  $section
     * @param  Array  $except
     * @return Array of stdClass Objects
     */  
    public function getActions($section, $except = [])
    {
	$actions = $this->getData('actions');

	if (!in_array($section, ['list', 'form', 'batch'])) {
	    return null;
	}

	if (!empty($except)) {
	    foreach ($actions->{$section} as $key => $action) {
		if (in_array($action->id, $except)) {
		    unset($actions->{$section}[$key]);
		}
	    } 
	}

	return $actions->{$section};
    }

    /*
     * Gets a json file related to a given item then returns the decoded data.
     *
     * @return Array of stdClass Objects / stdClass Object
     */  
    private function getData($type)
    {
	$json = file_get_contents(app_path().'/Models/'.ucfirst($this->pluginName).'/'.$this->modelName.'/'.$type.'.json', true);

        if ($json === false) {
	   throw new Exception('Load Failed');    
	}

	return json_decode($json);
    }
}
