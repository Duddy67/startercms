@extends ('layouts.admin')

@section ('header')
    <p class="h3">Permissions</p>
@endsection

@section ('main')
    <div class="card">
	<div class="card-body">
	    <x-toolbar :items=$actions />
	</div>
    </div>

    @foreach ($list as $section => $permissions)
	<h5 class="font-weight-bold">{{ $section }}</h5>
	<table class="table table-striped">
	    <tbody>
		@foreach ($permissions as $permission)
		    <tr><td>
                        {{ $permission }}
		    </td></tr>
		@endforeach
	    </tbody>
	</table>
    @endforeach

    <form id="updateItems" action="{{ route('admin.user.permissions.index') }}" method="post">
        @method('patch')
	@csrf
    </form>
@endsection

@push ('scripts')
    <script src="{{ asset('/js/admin/permissions/list.js') }}"></script>
@endpush
