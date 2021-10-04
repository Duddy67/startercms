<h3 class="pb-2">{{ $category->name }}</h3>

<div class="card">
    <div class="card-body">
	@include('partials.filters')
    </div>
</div>

<ul class="post-list pt-4">
    @if (count($posts))
	@foreach ($posts as $post)
	    @include ('partials.blog.post')
        @endforeach
    @else
        <div>No post</div>
    @endif
</ul>

<x-pagination :items=$posts />

<script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<script type="text/javascript" src="{{ url('/') }}/js/blog/category.js"></script>
