@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$toolbar />
    <x-item-list :columns=$columns :rows=$rows :items=$users />
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/item-list.js') }}"></script>
@endpush
