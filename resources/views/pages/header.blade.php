@inject ('menu', 'App\Models\Menus\Menu')
@php $menuItems = $menu::getMenu('main-menu')->getMenuItems(); @endphp

<nav class="navbar navbar-expand-md navbar-light bg-light">
  <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav mr-auto">
	    @foreach ($menuItems as $menuItem)
		@include ('partials.menuitems')
	    @endforeach
        </ul>
  </div>
</nav>

