@foreach ($menuItems as $menuItem)
    <li>
        <a href="{{ $menuItem->url }}" class="nav-item nav-link">{{ $menuItem->title }}</a>
    </li>
@endforeach
