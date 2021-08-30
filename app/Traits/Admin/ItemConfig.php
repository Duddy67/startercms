<?php

namespace App\Traits\Admin;

use App\Models\Settings\General;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;


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
     * Returns a row list.
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
	    $row = $this->getRow($columns, $item, $except);
	    $rows[] = $row;
	}

	return $rows;
    }

    /*
     * Returns a row tree list.
     *
     * @param Array of stdClass Objects  $columns
     * @param \Illuminate\Pagination\LengthAwarePaginator  $nodes
     * @param Array  $except 
     * @return Array of stdClass Objects
     */  
    public function getRowTree($columns, $nodes, $except = [])
    {
        $rows = [];

	$traverse = function ($items, $prefix = '-') use (&$traverse, &$rows, $columns, $except) {
	    foreach ($items as $item) {
		$row = $this->getRow($columns, $item, $except, $prefix);
		$rows[] = $row;

		$traverse($item->children, $prefix.'-');
	    }
	};

	$traverse($nodes);

	return $rows;
    }

    /*
     * Sets the values for a given item row.
     *
     * @param Array of stdClass Objects  $columns
     * @param Object  $item
     * @param Array   $except 
     * @param string  $prefix
     * @return stdClass Object
     */  
    private function getRow($columns, $item, $except = [], $prefix = '')
    {
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
		elseif ($column->name == 'owned_by') {
		    $row->owned_by = $item->user_name;
		}
		elseif ($column->name == 'access_level') {
		    $row->access_level = __('labels.generic.'.$item->access_level);
		}
		elseif ($column->name == 'status') {
		    $row->status = __('labels.generic.'.$item->status);
		}
		elseif ($column->name == 'name' && !empty($prefix)) {
		    $row->name = $prefix.' '.$item->name;
		}
		// Sets the ordering links according to the position of the item/node.
		elseif ($column->name == 'ordering') {
		    $ordering = [];

		    if ($item->getPrevSibling()) { 
		        $ordering['up'] = route('admin.'.$this->pluginName.'.'.Str::plural($this->modelName).'.up', $item->id);
		    }

		    if ($item->getNextSibling()) { 
		        $ordering['down'] = route('admin.'.$this->pluginName.'.'.Str::plural($this->modelName).'.down', $item->id);
		    }

		    $row->ordering = $ordering;
		}
		else {
		    $row->{$column->name} = $item->{$column->name};
		}
	    }
	    else {
		$row->{$column->name} = null;
	    }
	}

	return $row;
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
	    }

	    if ($item) {

		if ($field->type == 'select') {
		    $fields[$key]->value = $item->getSelectedValue($field->name);
		}
		elseif ($field->type == 'date') {
		    $fields[$key]->value = $item->{$field->name}->toDateString();
		}
		elseif ($field->name == 'updated_by') {
		    $fields[$key]->value = $item->modifier_name;
		}
		else {
		    $fields[$key]->value = $item->{$field->name};
		}

		if (method_exists($item, 'canEdit') && !$item->canEdit()) {
		    $field = $this->setExtraAttributes($field, ['disabled']);
		}

		if ($field->name == 'access_level' && method_exists($item, 'canChangeAccessLevel') && !$item->canChangeAccessLevel()) {
		    $field = $this->setExtraAttributes($field, ['disabled']);
		}
	    }

	    if ($field->name == 'owned_by' && count($field->options) == 1) {
	        // The current user is the only owner possible so let's get rid of the empty option.
		unset($fields[$key]->blank);
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
     * Adds one or more extra attributes to a given field.
     *
     * @param  stdClass $field
     * @param  Array  $attributes
     * @return stdClass Object
     */  
    public function setExtraAttributes($field, $attributes)
    {
	if (!isset($field->extra)) {
            $field->extra = $attributes;
	}
	elseif (!in_array('disabled', $field->extra)) {
	    foreach ($attributes as $attribute) {
		$field->extra[] = $attribute;
	    }
	}

	return $field;
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
