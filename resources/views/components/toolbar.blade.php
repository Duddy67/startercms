<nav class="navbar navbar-light bg-light">
    <div class="row">
	@foreach ($items as $item)
	    <div class="mr-3" id="action-btn">
		@if ($item->type == 'button')
                    <x-button :button=$item />
		@endif
	    </div>
	@endforeach
    </div>
</nav>
