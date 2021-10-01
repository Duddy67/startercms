<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menus\Menu;


class PostController extends Controller
{
    public function show($id, $slug)
    {
        $page = 'post';

        return view('default', compact('page', 'id', 'slug'));
    }
}
