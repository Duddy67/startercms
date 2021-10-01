@inject ('menu', 'App\Models\Menus\Menu')
@php $menuItems = $menu::getMenu('main-menu')->getMenuItems(); @endphp

<div>HEADER {{ $slug }}</div>
<nav class="navbar navbar-expand-md navbar-light bg-light">
  <div class="collapse navbar-collapse" id="navbarCollapse">
    <div class="navbar-nav">
        <ul>
	    @foreach ($menuItems as $menuItem)
		@include ('partials.menuitems')
	    @endforeach
        </ul>
    </div>
  </div>
</nav>

