<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menus\Menu;


class PostController extends Controller
{
    public function show($id, $slug)
    {
        $menu = Menu::where('code', 'main-menu')->first();
        $menuItems = $menu->getMenuItems();
file_put_contents('debog_file.txt', print_r($menuItems, true));
        $page = 'post';
        return view('default', compact('page', 'id', 'slug', 'menuItems'));
    }
}
