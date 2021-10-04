<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog\Category;


class SiteController extends Controller
{
    public function index(Request $request)
    {
        $page = 'site.home';

	if (!$category = Category::where('slug', 'drama')->first()) {
	    return abort('404');
	}

	$posts = $category->getPosts($request);
	$query = $request->query();

        return view('default', compact('page', 'category', 'posts', 'query'));
    }
}
