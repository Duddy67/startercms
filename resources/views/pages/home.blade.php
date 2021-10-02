<div>HOME {{ $category->name }}</div>

@foreach ($category->posts as $post)
    <div>{{ $post->title }}</div>
@endforeach
