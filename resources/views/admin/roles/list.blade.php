@extends ('layouts.admin')

@section ('header')
    <p class="h3">Roles</p>
@endsection

@section ('main')
    <div class="card">
	<div class="card-body">
	    <x-toolbar :items=$actions />
	</div>
    </div>

    @if (!empty($rows)) 
	<x-item-list :columns="$columns" :rows="$rows" :route="$route" />
    @else
        <div class="alert alert-info" role="alert">
	    No item has been found.
	</div>
    @endif

    <x-pagination :items=$items />

    <input type="hidden" id="createItem" value="{{ route('admin.roles.create', $query) }}">

    <form id="selectedItems" action="{{ route('admin.roles.index', $query) }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
