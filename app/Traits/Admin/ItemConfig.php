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

    public function getFields()
    {
	$fields = $this->getData('fields');

	// Possible operations here...

	return $fields;
    }

    public function getFilters()
    {
	$filters = $this->getData('filters');

	// Possible operations here...

	return $filters;
    }

    public function getToolbar()
    {
	$toolbar = $this->getData('toolbar');

	// Possible operations here...

	return $toolbar;
    }

    private function getData($type)
    {
	$json = file_get_contents(app_path().'/Models/'.$this->itemName.'/'.$type.'.json', true);

        if ($json === false) {
	   throw new Exception('Load Failed');    
	}

	return json_decode($json, true);
    }
}
