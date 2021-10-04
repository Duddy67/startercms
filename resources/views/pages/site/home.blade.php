<ul class="post-list">
    @if (count($posts))
	@foreach ($posts as $post)
	    @include ('partials.blog.post')
        @endforeach
    @else
        <div>No post</div>
    @endif
</ul>


<script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<script type="text/javascript" src="{{ url('/') }}/js/blog/category.js"></script>
