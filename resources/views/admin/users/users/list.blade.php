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

    <div class="card">
	<div class="card-body">
	    <x-filters :filters="$filters" :url="$url" />
	</div>
    </div>

    @if (!empty($rows)) 
	<x-item-list :columns="$columns" :rows="$rows" :url="$url" />
    @else
        <div class="alert alert-info" role="alert">
	    No item has been found.
	</div>
    @endif

    <x-pagination :items="$items" />

    <input type="hidden" id="createItem" value="{{ route('admin.users.users.create', $query) }}">

    <form id="selectedItems" action="{{ route('admin.users.users.index', $query) }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script type="text/javascript" src="{{ url('/') }}/js/admin/list.js"></script>
@endpush
