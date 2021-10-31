<h3><a href="{{ url($post->getUrl()) }}">{{ $post->title }}</a></h3>

<div>{{ $post->created_at }}</div>
<div>{{ $post->owner_name }}</div>
<p class="content">{!! $post->content !!}</p>

<p class="categories">
    <h6>Categories</h6>
    @foreach ($post->categories as $category)
	<a href="{{ url($category->getUrl()) }}" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">{{ $category->name }}</a>
    @endforeach
</p>
