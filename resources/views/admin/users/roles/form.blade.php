@extends ('layouts.admin')

@section ('main')
    <h3>@php echo (isset($role)) ? __('labels.roles.edit_role') : __('labels.roles.create_role'); @endphp</h3>

    @php $action = (isset($role)) ? route('admin.users.roles.update', $query) : route('admin.users.roles.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($role))
	    @method('put')
	@endif

        @foreach ($fields as $field)
	    @php if (isset($role)) { 
		     $value = old($field->name, $field->value);

                     if ($field->name == 'access_level' && $role->role_level > auth()->user()->getRoleLevel()) {
                         $field->extra = ['disabled'];
                     }

                     if ($field->name == 'role_type') {
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

	    <div class="form-group">
		<x-input :field="$field" :value="$value" />
            </div>
        @endforeach

	<h4 class="pt-3">Permissions</h4>
	@foreach ($board as $section => $checkboxes)
	    <h5 class="font-weight-bold">{{ $section }}</h5>
	    <table class="table table-striped">
		<tbody>
		    @foreach ($checkboxes as $checkbox)
			<tr>
			    <td>
                                <div class="form-check">
				    <x-input :field="$checkbox" :value="$checkbox->value" />
                                </div>
			    </td>
                        </tr>
		    @endforeach
		</tbody>
	    </table>
	@endforeach

	<input type="hidden" id="cancelEdit" value="{{ route('admin.users.roles.cancel', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">

	@if (!isset($role))
	    <input type="hidden" id="permissions" name="_permissions" value="{{ $permissions }}">
        @endif
    </form>

    <div class="form-group">
	<x-toolbar :items=$actions />
    </div>

    @if (isset($role))
	<form id="deleteItem" action="{{ route('admin.users.roles.destroy', $query) }}" method="post">
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
    <script type="text/javascript" src="{{ url('/') }}/js/admin/roles/settings.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/disable.toolbars.js"></script>
@endpush
