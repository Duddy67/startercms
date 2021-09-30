<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function show($id, $slug)
    {
        $page = 'category';
        return view('default', compact('page', 'id', 'slug'));
    }
}
