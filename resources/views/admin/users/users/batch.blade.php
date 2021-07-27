<!-- Theme style -->
<link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/css/adminlte.min.css">
<!-- Custom style -->
<link rel="stylesheet" href="{{ url('/') }}/css/admin/style.css">
<!-- Font Awesome Icons -->
<link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/plugins/fontawesome-free/css/all.min.css">
<script src="{{ url('/') }}/vendor/adminlte/plugins/jquery/jquery.min.js"></script>
<script src="{{ url('/') }}/vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="{{ url('/') }}/vendor/bootstrap-4.5.3/css/bootstrap.min.css">
<!-- Select2 Plugin -->
<script type="text/javascript" src="{{ url('/') }}/vendor/adminlte/plugins/select2/js/select2.min.js"></script>
<link rel="stylesheet" href="{{ url('/') }}/vendor/adminlte/plugins/select2/css/select2.min.css"></script>

<form method="post" action="{{ route('admin.users.users.massUpdate', $query) }}" id="batchForm" target="_parent">
    @csrf
    @method('put')

    @foreach ($fields as $attribs)
	@php $value = null; @endphp
	<div class="form-group">
	    <x-input :attribs="$attribs" :value="$value" />
	</div>
    @endforeach

    <input type="hidden" id="itemList" value="{{ route('admin.users.users.index', $query) }}">
</form>

<div class="form-group">
    <x-toolbar :items=$actions />
</div>

<script type="text/javascript" src="{{ url('/') }}/js/admin/batch.js"></script>

