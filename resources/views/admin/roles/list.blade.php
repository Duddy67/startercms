@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />

    @if (!empty($rows)) 
	<x-item-list :columns="$columns" :rows="$rows" route="admin.roles.edit" />
    @else
        <div class="alert alert-info" role="alert">
	    No item has been found.
	</div>
    @endif

    <input type="hidden" id="listUrl" value="{{ route('admin.roles.index') }}">

    <form id="selectedItems" action="{{ route('admin.roles.index') }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
