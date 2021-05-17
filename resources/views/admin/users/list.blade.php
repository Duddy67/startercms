@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />
    <x-item-list :columns=$columns :rows=$rows :items=$users />
    <input type="hidden" id="listUrl" value="{{ route('admin.users') }}">
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/item-list.js') }}"></script>
@endpush
