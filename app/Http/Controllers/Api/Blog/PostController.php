<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;


class PostController extends Controller
{
    public function index()
    {
        $posts = Post::all();

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        // code...
    }

    public function show($post)
    {
	if (!$post = Post::select('title', 'slug', 'content')->find($post)) {
	    return response()->json([
		'message' => 'Ressource not found.'
	    ], 404);
	}

        return response()->json($post);
    }

    public function update(Request $request, Post $post)
    {
        // code...
    }

    public function destroy(Post $post)
    {
        // code...
    }
}
