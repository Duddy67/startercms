@extends ('layouts.admin')

@section ('main')
    <form method="post" action="{{ route('admin.settings.general.update', $query ) }}" id="itemForm">
        @csrf
	@method('patch')

        @foreach ($fields as $field)
	    @php if (isset($data[$field->group][$field->name])) { 
		     $value = old($field->name, $data[$field->group][$field->name]);
		 }
		 else {
		     $value = old($field->name);
		 }
	    @endphp
	    <x-input :field="$field" :value="$value" />
        @endforeach
    </form>

    <div class="form-group">
	<x-toolbar :items=$actions />
    </div>
@endsection

@push ('scripts')
    <script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.css"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/datepicker.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/form.js"></script>
@endpush
