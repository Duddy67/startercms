<form id="item-filters" action="{{ route($url['route'].'.index') }}" method="get">
    <div class="row">
        @php $total = count($filters) @endphp
	@foreach ($filters as $key => $filter)
	    <div class="col-sm mr-4">
		<x-input :attribs="$filter" :value="$filter->value" />

		@if ($filter->name == 'search') 
		    <button type="button" id="search-btn" class="btn btn-space btn-secondary"> Search</button>
		    <button type="button" id="clear-search-btn" class="btn btn-space btn-secondary"> Clear</button>
		@endif

		@if ($total == ($key + 1)) 
		    <button type="button" id="clear-all-btn" class="btn btn-space btn-secondary"> Clear all</button>
		@endif
	    </div>
	@endforeach
    </div>

    @if (isset($url['query']['page'])) 
	<input type="hidden" id="filters-pagination" name="page" value="{{ $url['query']['page'] }}">
    @endif
</form>
