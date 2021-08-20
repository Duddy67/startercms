@extends ('layouts.admin')

@section ('main')
    <h3>@php echo (isset($group)) ? __('labels.groups.edit_group') : __('labels.groups.create_group'); @endphp</h3>

    @php $action = (isset($group)) ? route('admin.users.groups.update', $query) : route('admin.users.groups.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($group))
	    @method('put')
	@endif

        @foreach ($fields as $field)
	    @php if (isset($group)) { 
		     $value = old($field->name, $field->value);

                     if ($field->name == 'access_level' && $group->role_level > auth()->user()->getRoleLevel()) {
                         $field->extra = ['disabled'];
                     }
		 }
		 else {
                     if ($field->name == 'created_at' || $field->name == 'updated_at') {
                         continue;
                     }

		     $value = old($field->name);
		 }
	    @endphp
	    <x-input :field="$field" :value="$value" />
        @endforeach

	<input type="hidden" id="cancelEdit" value="{{ route('admin.users.groups.cancel', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($group))
	<form id="deleteItem" action="{{ route('admin.users.groups.destroy', $query) }}" method="post">
	    @method('delete')
	    @csrf
	</form>
    @endif
@endsection

@push ('scripts')
    <script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/vendor/tinymce-5.8.2/tinymce.min.js"></script>
    <link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.css"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/datepicker.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/form.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/disable.toolbars.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/tinymce/filemanager.js"></script>
@endpush
