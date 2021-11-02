<h3><a href="{{ url($post->getUrl()) }}">{{ $post->title }}</a></h3>

@if ($settings['show_created_at'])
    <div>{{ $post->created_at }}</div>
@endif

@if ($settings['show_owner'])
    <div>{{ $post->owner_name }}</div>
@endif

<p class="content">{!! $post->content !!}</p>

@if ($settings['show_categories'])
    <p class="categories">
	<h6>Categories</h6>
	@foreach ($post->categories as $category)
	    <a href="{{ url($category->getUrl()) }}" class="btn btn-primary btn-sm active" role="button" aria-pressed="true">{{ $category->name }}</a>
	@endforeach
    </p>
@endif
