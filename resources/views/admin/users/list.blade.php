@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />
    <x-item-list :columns="$columns" :rows="$rows" :items="$users" route="admin.users.edit" />
    <input type="hidden" id="listUrl" value="{{ route('admin.users.index') }}">

    <form id="selectedItems" action="{{ route('admin.users.index') }}" method="post">
	@method('delete')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
