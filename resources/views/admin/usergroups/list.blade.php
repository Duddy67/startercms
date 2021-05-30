@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />
    <x-item-list :columns="$columns" :rows="$rows" :items="$userGroups" route="admin.usergroups.edit" />
    <input type="hidden" id="listUrl" value="{{ route('admin.usergroups.index') }}">

    <form id="selectedItems" action="{{ route('admin.usergroups.index') }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
