@extends ('layouts.admin')

@section ('main')
    <x-item-list :columns=$columns :items=$users />
@endsection
