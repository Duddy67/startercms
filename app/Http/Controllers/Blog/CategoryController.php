<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Category;
use App\Models\Blog\Setting;
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

	$globalSettings = Setting::getDataByGroup('category');
	$settings = [];

	foreach ($category->settings as $key => $value) {
	    if ($value == 'global_setting') {
	        $settings[$key] = $globalSettings[$key];
	    }
	    else {
	        $settings[$key] = $category->settings[$key];
	    }
	}

	$posts = $category->getPosts($request);
	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);
	$canView = (Auth::check() && $category->canAccess()) ? true : false;

        return view('default', compact('page', 'category', 'settings', 'posts', 'query', 'canView'));
    }
}
