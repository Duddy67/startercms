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

    @if (!empty($rows)) 
	<form id="item-filters" action="{{ route('admin.users.index') }}" method="get">

	    @foreach ($filters as $attribs)
		<x-input :attribs="$attribs" :value="$attribs->value" />
	    @endforeach
	</form>

	<x-item-list :columns="$columns" :rows="$rows" :route="$route" />
    @else
        <div class="alert alert-info" role="alert">
	    No item has been found.
	</div>
    @endif

    <x-pagination :items=$items />

    <input type="hidden" id="createItem" value="{{ route('admin.users.create', $query) }}">

    <form id="selectedItems" action="{{ route('admin.users.index', $query) }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
