<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;


class PostController extends Controller
{
    public function show($id, $slug)
    {
	if (!$post = Post::where('id', $id)->first()) {
	    return abort('404');
	}

        $page = 'blog.post';

        return view('default', compact('page', 'id', 'slug', 'post'));
    }
}
