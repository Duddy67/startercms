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
	<x-item-list :columns="$columns" :rows="$rows" route="admin.users.edit" />
    @else
        <div class="alert alert-info" role="alert">
	    No item has been found.
	</div>
    @endif

    {{ $users->links('pagination::bootstrap-4') }}
    <div>Showing {{ $users->firstItem() }} to {{ $users->lastItem() }} of {{ $users->total() }} results.</div>
    
    
    

    <input type="hidden" id="listUrl" value="{{ route('admin.users.index') }}">

    <form id="selectedItems" action="{{ route('admin.users.index') }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
