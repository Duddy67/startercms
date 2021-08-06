@extends ('layouts.admin')

@section ('main')

    @php $action = (isset($user)) ? route('admin.users.users.update', $query) : route('admin.users.users.store', $query) @endphp

    @if (isset($user) && $photo) 
        <img src="{{ url('/').$photo->getThumbnailUrl() }}" >
    @endif

    <form method="post" action="{{ $action }}" id="itemForm" enctype="multipart/form-data">
        @csrf

	@if (isset($user))
	    @method('put')
	@endif

        @foreach ($fields as $field)
	    @php if (isset($user)) { 
		     $value = old($field->name, $field->value);
                     // Users cannot change their role.
                     if ($field->name == 'role' && auth()->user()->id == $user->id) {
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

	<input type="hidden" id="cancelEdit" value="{{ route('admin.users.users.cancel', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>

    <div class="form-group">
	<x-toolbar :items=$actions />
    </div>

    @if (isset($user))
	<form id="deleteItem" action="{{ route('admin.users.users.destroy', $query) }}" method="post">
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
    <script type="text/javascript" src="{{ url('/') }}/js/admin/disable.toolbars.js"></script>
@endpush
