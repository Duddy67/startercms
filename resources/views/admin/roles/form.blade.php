@extends ('layouts.admin')

@section ('main')
    @php $action = (isset($role)) ? route('admin.roles.update', $role->id) : route('admin.roles.store') @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf
        @foreach ($fields as $attribs)
	    @php if (isset($role)) { 
		     $value = old($attribs->name, $attribs->value);
		 }
		 else {
                     if ($attribs->name == 'created_at' || $attribs->name == 'updated_at') {
                         continue;
                     }

		     $value = old($attribs->name);
		 }
	    @endphp
	    <x-input :attribs=$attribs :value=$value />
        @endforeach
	<input type="hidden" id="listUrl" value="{{ route('admin.roles.index') }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($role))
	<form id="deleteItemForm" action="{{ url('/admin/roles', ['id' => $role->id]) }}" method="post">
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
