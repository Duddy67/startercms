@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />
    <x-item-list :columns="$columns" :rows="$rows" :items="$permissions" route="admin.permissions.edit" />
    <input type="hidden" id="listUrl" value="{{ route('admin.permissions.index') }}">

    <form id="selectedItems" action="{{ route('admin.permissions.index') }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
