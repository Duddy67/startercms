@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />
    <x-item-list :columns="$columns" :rows="$rows" :items="$roles" route="admin.roles.edit" />
    <input type="hidden" id="listUrl" value="{{ route('admin.roles.index') }}">

    <form id="selectedItems" action="{{ route('admin.roles.index') }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
