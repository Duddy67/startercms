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

    <x-pagination :items=$items />

    <input type="hidden" id="createItem" value="{{ route('admin.user.roles.create', $query) }}">
    <input type="hidden" id="destroyItems" value="{{ route('admin.user.roles.index', $query) }}">
    <input type="hidden" id="checkinItems" value="{{ route('admin.user.roles.massCheckIn', $query) }}">

    <form id="selectedItems" action="{{ route('admin.user.roles.index', $query) }}" method="post">
        @method('delete')
        @csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/list.js') }}"></script>
@endpush
