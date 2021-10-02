<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Blog\Category;


class SiteController extends Controller
{
    public function index(Request $request)
    {
        $page = 'home';

	$category = Category::where('id', 5)->first();

        return view('default', compact('page', 'category'));
    }
}
