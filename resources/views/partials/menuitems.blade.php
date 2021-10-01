<li>
    <a href="{{ url($menuItem->url) }}" class="nav-item nav-link">{{ $menuItem->title }}</a>
</li>
@if (count($menuItem->children) > 0)
    <ul>
	@foreach ($menuItem->children as $menuItem)
	    @include ('partials.menuitems')
	@endforeach
    </ul>
@endif
