<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Menus\Menu;
use App\Models\Menus\MenuItem;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
	$menu = Menu::create([
	  'title' => 'Main menu',
	  'code' => 'main-menu',
	  'status' => 'published',
	  'access_level' => 'public_ro',
	  'owned_by' => 1,
	]);

        // Creates the root item which is the parent of all of the menu items. 
        $node = new MenuItem;
	$node->title = 'Root';
	$node->menu_code = 'root';
	$node->url = 'root';
	$node->status = 'published';
	$node->access_level = 'public_ro';
	$node->owned_by = 1;
        // Saved as root
	$node->save();

	$menuItem = MenuItem::create([
	    'title' => 'Home',
	    'url' => '/',
	    'status' => 'published',
	    'access_level' => 'public_ro',
	    'owned_by' => 1,
	    'parent_id' => 1,
	]);

	$parent = MenuItem::findOrFail($menuItem->parent_id);
	$parent->appendNode($menuItem);

	$menuItem->menu_code = 'main-menu';
	$menuItem->save();
    }
}
