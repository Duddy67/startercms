<?php

namespace App\Traits\Admin;

use App\Models\Settings\General;


trait ItemConfig
{

    public function getColumns()
    {
	$columns = $this->getData('columns');

	// Possible operations here...

	return $columns;
    }

    public function getRows($columns, $items, $except = [])
    {
        $rows = [];

        foreach ($items as $item) {
	    $row = array('item_id' => $item->id);

	    foreach ($columns as $column) {
	        if (!in_array($column->id, $except)) {

		    if ($column->type == 'date') {
			$row[$column->id] = $item->{$column->id}->toFormattedDateString();
		    }
		    else {
			$row[$column->id] = $item->{$column->id};
		    }
		}
		else {
		    $row[$column->id] = null;
		}
	    }

	    $rows[] = $row;
	}

	return $rows;
    }

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
		$options = $this->model->$function($item);
		$fields[$key]->options = $options;

		if ($item) {
		    $function = 'get'.str_replace('_', '', ucwords($field->name, '_')).'Value';
		    $fields[$key]->value = $item->$function();
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

    public function getFilters($request)
    {
	$filters = $this->getData('filters');

	foreach ($filters as $key => $filter) {
	    if ($filter->type == 'button') {
	        continue;
	    }

	    if ($filter->type == 'select') {
		// Build the function name.
		$function = 'get'.str_replace('_', '', ucwords($filter->name, '_')).'Options';

		// General filter.
		if ($filter->name == 'per_page') {
		    $options = General::$function();
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

	    $filters[$key]->value = $request->input($filter->name);
	}

	return $filters;
    }

    public function getActions($section, $except = [])
    {
	$actions = $this->getData('actions');

	if ($section != 'list' && $section != 'form') {
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

    private function getData($type)
    {
	$json = file_get_contents(app_path().'/Models/'.ucfirst($this->pluginName).'/'.$this->modelName.'/'.$type.'.json', true);

        if ($json === false) {
	   throw new Exception('Load Failed');    
	}

	return json_decode($json);
    }
}
