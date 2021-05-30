<?php

namespace App\Traits\Admin;


trait ItemConfig
{
    public $itemName;
    public $itemModel;


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

	// Set the select field types.
	foreach ($fields as $key => $field) {
	    if ($field->type == 'select') {
	        // Build the function name.
		$function = 'get'.ucfirst($field->name).'Options';
		$options = $this->itemModel::$function($item);
		$fields[$key]->options = $options;

		if ($item) {
		    $function = 'get'.ucfirst($field->name).'Value';
		    $fields[$key]->value = $item->$function();
		}
	    }
	}

	if ($item) {
	    foreach ($fields as $key => $field) {
		if (isset($field->name) && !in_array($field->type, $except)) {
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

    public function getFilters()
    {
	$filters = $this->getData('filters');

	// Possible operations here...

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
	$json = file_get_contents(app_path().'/Models/'.$this->itemName.'/'.$type.'.json', true);

        if ($json === false) {
	   throw new Exception('Load Failed');    
	}

	return json_decode($json);
    }
}
