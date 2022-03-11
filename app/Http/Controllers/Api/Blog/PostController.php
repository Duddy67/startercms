<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;


class PostController extends Controller
{
    public function index(Request $request)
    {
        if (auth('api')->user()) {
            $posts = Post::select('id', 'title', 'slug', 'excerpt', 'content')->get();
        }
        else {
            $posts = Post::select('id', 'title', 'content')->get();
        }

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        // code...
        return $request->all();
    }

    public function show($post)
    {
        if (!$post = Post::select('id', 'title', 'slug', 'excerpt', 'content')->find($post)) {
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
