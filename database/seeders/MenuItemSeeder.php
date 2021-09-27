<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menus\MenuItem;

class MenuItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Creates the root item which is the parent of all of the menu items. 
        $node = new MenuItem;
	$node->title = 'Root item';
	$node->menu_code = 'root-item';
	$node->url = 'root';
	$node->status = 'published';
	$node->access_level = 'public_ro';
	$node->owned_by = 1;
        // Saved as root
	$node->save();
    }
}
