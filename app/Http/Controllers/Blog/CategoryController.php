<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Category;
use App\Models\Settings\General;
use Illuminate\Support\Facades\Auth;


class CategoryController extends Controller
{
    public function index(Request $request, $id, $slug)
    {
        $page = 'blog.category';

	if (!$category = Category::where('id', $id)->first()) {
	    return abort('404');
	}

	$posts = $category->getPosts($request);
	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);
	$canView = (Auth::check() && $category->canAccess()) ? true : false;

        return view('default', compact('page', 'category', 'posts', 'query', 'canView'));
    }
}
