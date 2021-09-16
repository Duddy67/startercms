@extends ('layouts.admin')

@section ('main')
    <h3>@php echo (isset($category)) ? __('labels.categories.edit_category') : __('labels.categories.create_category'); @endphp</h3>

    @php $action = (isset($category)) ? route('admin.blog.categories.update', $query) : route('admin.blog.categories.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($category))
	    @method('put')
	@endif

        @foreach ($fields as $field)
	    @php $value = (isset($category)) ? old($field->name, $field->value) : old($field->name); @endphp
	    <x-input :field="$field" :value="$value" />
        @endforeach

	<input type="hidden" id="cancelEdit" value="{{ route('admin.blog.categories.cancel', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($category))
	<form id="deleteItem" action="{{ route('admin.blog.categories.destroy', $query) }}" method="post">
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
    <script type="text/javascript" src="{{ url('/') }}/js/select2.locked.options.js"></script>
@endpush
