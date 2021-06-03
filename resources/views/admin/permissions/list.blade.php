@extends ('layouts.admin')

@section ('main')
    <x-toolbar :items=$actions />

    @foreach ($list as $section => $permissions)
	<h5>{{ $section }}</h5>
	<table class="table">
	    <tbody>
		@foreach ($permissions as $permission)
		    <tr><td>
                        {{ $permission }}
		    </td></tr>
		@endforeach
	    </tbody>
	</table>
    @endforeach

    <form id="updateItems" action="{{ route('admin.permissions.index') }}" method="post">
        @method('patch')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/permissions/list.js') }}"></script>
@endpush
