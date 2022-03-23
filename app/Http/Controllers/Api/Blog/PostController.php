<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;


class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();
        $query->select('id', 'title', 'content')->where('access_level', 'public_ro')->orWhere('access_level', 'public_ro');

        if (auth('api')->user()) {
            $query->orWhere('owned_by', auth('api')->user()->id);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        // code...
        return $request->all();
    }

    public function show($post)
    {
        if (!$post = Post::select('id', 'title', 'slug', 'access_level', 'owned_by', 'excerpt', 'content')->find($post)) {
            return response()->json([
                'message' => 'Ressource not found.'
            ], 404);
        }

        // Check for private posts.
        if ($post->access_level == 'private' && (!auth('api')->user() || auth('api')->user()->id != $post->owned_by)) {
            return response()->json([
                'message' => 'You are not authorised to access this ressource.'
            ], 403);
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
