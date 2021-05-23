@extends ('layouts.admin')

@section ('main')
    @php $action = (isset($permission)) ? route('admin.permissions.update', $permission->id) : route('admin.permissions.store') @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($permission))
	    @method('put')
	@endif

        @foreach ($fields as $attribs)
	    @php if (isset($permission)) { 
		     $value = old($attribs->name, $attribs->value);
		 }
		 else {
                     if ($attribs->name == 'created_at' || $attribs->name == 'updated_at') {
                         continue;
                     }

		     $value = old($attribs->name);
		 }
	    @endphp
	    <x-input :attribs="$attribs" :value="$value" />
        @endforeach

	<input type="hidden" id="listUrl" value="{{ route('admin.permissions.index') }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($permission))
	<form id="deleteItemForm" action="{{ url('/admin/permissions', ['id' => $permission->id]) }}" method="post">
	    @method('delete')
	    @csrf
	</form>
    @endif
@endsection

@push ('scripts')
    <script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.css"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/datepicker.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/form.js"></script>
@endpush
