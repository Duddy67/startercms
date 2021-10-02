<div>{{ $category->name }} {{ $slug }} {{ $id }}</div>

<ul class="post-list">
    @foreach ($category->posts as $post)
        @include ('partials.post')
    @endforeach
</ul>
