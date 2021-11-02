<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog\Post;
use App\Models\Blog\Setting;
use Illuminate\Support\Facades\Auth;


class PostController extends Controller
{
    public function show(Request $request, $id, $slug)
    {
        $post = Post::select('posts.*', 'users.name as owner_name')
			->selectRaw('IFNULL(users2.name, ?) as modifier_name', [__('labels.generic.unknown_user')])
			->leftJoin('users', 'posts.owned_by', '=', 'users.id')
			->leftJoin('users as users2', 'posts.updated_by', '=', 'users2.id')
			->where('posts.id', $id)->first();

	if (!$post) {
	    return abort('404');
	}

	if (!$post->canAccess()) {
	    return abort('403');
	}

        $page = 'blog.post';

	$globalSettings = Setting::getDataByGroup('posts');
	$settings = [];

	foreach ($post->settings as $key => $value) {
	    if ($value == 'global_setting') {
	        $settings[$key] = $globalSettings[$key];
	    }
	    else {
	        $settings[$key] = $post->settings[$key];
	    }
	}

	$query = array_merge($request->query(), ['id' => $id, 'slug' => $slug]);

        return view('default', compact('page', 'id', 'slug', 'post', 'settings', 'query'));
    }
}
