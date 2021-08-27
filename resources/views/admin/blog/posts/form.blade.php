@extends ('layouts.admin')

@section ('main')
    <h3>@php echo (isset($post)) ? __('labels.posts.edit_post') : __('labels.posts.create_post'); @endphp</h3>

    @php $action = (isset($post)) ? route('admin.blog.posts.update', $query) : route('admin.blog.posts.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($post))
	    @method('put')
	@endif

        @foreach ($fields as $field)
	    @php $value = (isset($post)) ? old($field->name, $field->value) : old($field->name); @endphp
	    <x-input :field="$field" :value="$value" />
        @endforeach

	<input type="hidden" id="cancelEdit" value="{{ route('admin.blog.posts.cancel', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($post))
	<form id="deleteItem" action="{{ route('admin.blog.posts.destroy', $query) }}" method="post">
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
