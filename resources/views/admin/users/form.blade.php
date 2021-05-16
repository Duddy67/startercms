@extends ('layouts.admin')

@section ('main')
    @php $action = (isset($user)) ? route('admin.users.update', ['id' => $user->id]) : route('admin.users.create') @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf
        @foreach ($fields as $attribs)
	    <x-input :attribs=$attribs />
        @endforeach
    </form>
    <x-toolbar :items=$actions />
@endsection

@push ('scripts')
    <script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.js"></script>
    <link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/plugins/jquery-ui/jquery-ui.min.css"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/datepicker.js"></script>
    <script type="text/javascript" src="{{ url('/') }}/js/admin/form.js"></script>
@endpush
