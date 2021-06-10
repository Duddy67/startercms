@extends ('layouts.admin')

@section ('header')
    <p class="h3">Users</p>
@endsection

@section ('main')
    <div class="card">
	<div class="card-body">
	    <x-toolbar :items=$actions />
	</div>
    </div>

    <form id="item-filters" action="{{ route('admin.users.index') }}" method="get">
	@foreach ($filters as $attribs)
	    @if ($attribs->type == 'button') 
		<x-button :button="$attribs" />
	    @else
		<x-input :attribs="$attribs" :value="$attribs->value" />
	    @endif
	@endforeach

	@if (isset($query['page'])) 
	    <input type="hidden" id="filters-pagination" name="page" value="{{ $query['page'] }}">
	@endif
    </form>

    @if (!empty($rows)) 
	<x-item-list :columns="$columns" :rows="$rows" :route="$route" />
    @else
        <div class="alert alert-info" role="alert">
	    No item has been found.
	</div>
    @endif

    <x-pagination :items="$items" />

    <input type="hidden" id="createItem" value="{{ route('admin.users.create', $query) }}">

    <form id="selectedItems" action="{{ route('admin.users.index', $query) }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script type="text/javascript" src="{{ url('/') }}/js/admin/list.js"></script>
@endpush
