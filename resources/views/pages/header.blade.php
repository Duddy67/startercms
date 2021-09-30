<div>HEADER {{ $slug }}</div>
<nav class="navbar navbar-expand-md navbar-light bg-light">
  <div class="collapse navbar-collapse" id="navbarCollapse">
    <div class="navbar-nav">
      @include ('pages.menuitems')
      <a href="{{ url('/') }}" class="nav-item nav-link">Home</a>
      <a href="{{ url('/') }}" class="nav-item nav-link">Categories</a>
    </div>
  </div>
</nav>

