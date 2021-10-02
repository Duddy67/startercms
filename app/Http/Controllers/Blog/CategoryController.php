<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Category;


class CategoryController extends Controller
{
    public function index(Request $request, $id, $slug)
    {
        $page = 'category';

	$category = Category::where('id', $id)->first();

        return view('default', compact('page', 'id', 'slug', 'category'));
    }
}
