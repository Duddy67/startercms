@extends ('layouts.admin')

@section ('main')
    <h3>@php echo (isset($menuItem)) ? __('labels.menuitems.edit_menu_item') : __('labels.menuitems.create_menu_item'); @endphp</h3>

    @php $action = (isset($menuItem)) ? route('admin.menus.menuitems.update', $query) : route('admin.menus.menuitems.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($menuItem))
	    @method('put')
	@endif

        @foreach ($fields as $field)
	    @php $value = (isset($menuItem)) ? old($field->name, $field->value) : old($field->name); @endphp
	    <x-input :field="$field" :value="$value" />
        @endforeach

	<input type="hidden" id="cancelEdit" value="{{ route('admin.menus.menuitems.cancel', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($menuItem))
	<form id="deleteItem" action="{{ route('admin.menus.menuitems.destroy', $query) }}" method="post">
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
    <script type="text/javascript" src="{{ asset('/js/admin/datepicker.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/form.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/set.private.groups.js') }}"></script>
    <script type="text/javascript" src="{{ asset('/js/admin/disable.toolbars.js') }}"></script>
@endpush
