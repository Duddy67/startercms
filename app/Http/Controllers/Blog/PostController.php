<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;


class PostController extends Controller
{
    public function show($id, $slug)
    {
        $post = Post::select('posts.*', 'users.name as owner_name')
			->selectRaw('IFNULL(users2.name, ?) as modifier_name', [__('labels.generic.unknown_user')])
			->leftJoin('users', 'posts.owned_by', '=', 'users.id')
			->leftJoin('users as users2', 'posts.updated_by', '=', 'users2.id')
			->where('posts.id', $id)->first();

	if (!$post) {
	    return abort('404');
	}

        $page = 'blog.post';

        return view('default', compact('page', 'id', 'slug', 'post'));
    }
}
