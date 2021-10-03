<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;


class PostController extends Controller
{
    public function show($id, $slug)
    {
	$post = Post::where('id', $id)->first();

        $page = 'post';

        return view('default', compact('page', 'id', 'slug', 'post'));
    }
}
