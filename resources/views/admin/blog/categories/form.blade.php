@extends ('layouts.admin')

@section ('main')
    <h3>@php echo (isset($category)) ? __('labels.categories.edit_category') : __('labels.categories.create_category'); @endphp</h3>

    @php $action = (isset($category)) ? route('admin.blog.categories.update', $query) : route('admin.blog.categories.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($category))
	    @method('put')
	@endif

	<nav class="nav nav-tabs">
	    <a class="nav-item nav-link active" href="#details" data-toggle="tab">@php echo __('labels.generic.details'); @endphp</a>
	    <a class="nav-item nav-link" href="#settings" data-toggle="tab">@php echo __('labels.title.settings'); @endphp</a>
	</nav>

	<div class="tab-content">
	    @foreach ($fields as $key => $field)
	        @if ($key == 0)
		    <div class="tab-pane active" id="details">
	        @endif

		@php $value = (isset($category)) ? old($field->name, $field->value) : old($field->name); @endphp
		<x-input :field="$field" :value="$value" />

	        @if ($field->name == 'description')
		    </div>
		    <div class="tab-pane" id="settings">
	        @endif
	    @endforeach
	    </div>
	</div>

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

@push ('style')
    <link rel="stylesheet" href="{{ asset('/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.css') }}">
@endpush

@push ('scripts')
    <script type="text/javascript" src="{{ asset('/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/vendor/tinymce/tinymce.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/datepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/set.private.groups.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/disable.toolbars.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/tinymce/filemanager.js') }}"></script>
@endpush
