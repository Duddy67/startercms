<?php

namespace App\Traits\Admin;


trait ItemConfig
{
    public $itemName;


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

	if ($item) {
	    foreach ($fields as $key => $field) {
		if (isset($field->name) && !in_array($field->type, $except)) {
		    if ($field->type == 'date') {
			$fields[$key]->value = $item->{$field->name}->toDateString();
		    }
		    elseif ($field->type == 'select') {
		        $getOptions = 'get'.ucfirst($field->name).'Options';
			$options = $item->$getOptions();
			$fields[$key]->options = $options;
		        $getValue = 'get'.ucfirst($field->name).'Value';
			$fields[$key]->value = $item->$getValue();
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

//file_put_contents('debog_file.txt', print_r($fields, true));
	return $fields;
    }

    public function getFilters()
    {
	$filters = $this->getData('filters');

	// Possible operations here...

	return $filters;
    }

    public function getActions($section)
    {
	$actions = $this->getData('actions');

	if ($section != 'list' && $section != 'form') {
	    return null;
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
