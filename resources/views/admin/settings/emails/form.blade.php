@extends ('layouts.admin')

@section ('main')
    @php $action = (isset($email)) ? route('admin.settings.emails.update', $queryWithId) : route('admin.settings.emails.store', $query) @endphp
    <form method="post" action="{{ $action }}" id="itemForm">
        @csrf

	@if (isset($email))
	    @method('put')
	@endif

        @foreach ($fields as $attribs)
	    @php if (isset($email)) { 
		     $value = old($attribs->name, $attribs->value);
		 }
		 else {
                     if ($attribs->name == 'created_at' || $attribs->name == 'updated_at') {
                         continue;
                     }

		     $value = old($attribs->name);
		 }
	    @endphp

	    @if ($attribs->name == 'body_html')
	        <ul class="nav nav-tabs" id="myTab" role="tablist">
		    <li class="nav-item">
			<a  class="nav-link active" id="html-tab" href="#html" data-toggle="tab" aria-controls="html" aria-selected="true">HTML</a>
		    </li>
		    <li class="nav-item">
                        <a class="nav-link" id="text-tab" href="#text" data-toggle="tab" aria-controls="text" aria-selected="false">Plain text</a>
                    </li>
                </ul>

		<div class="tab-content" id="myTabContent">
		    <div class="tab-pane active" id="html" role="tabpanel" aria-labelledby="html-tab">
	    @endif

	    @if ($attribs->name == 'body_text')
	        <div class="tab-pane" id="text" role="tabpanel" aria-labelledby="text-tab">
	    @endif

	    <x-input :attribs="$attribs" :value="$value" />

	    @if ($attribs->name == 'body_html')
	        </div>
	    @endif

	    @if ($attribs->name == 'body_text')
	        </div>
	        </div>
	    @endif
        @endforeach

	<input type="hidden" id="itemList" value="{{ route('admin.settings.emails.index', $query) }}">
	<input type="hidden" id="close" name="_close" value="0">
    </form>
    <x-toolbar :items=$actions />

    @if (isset($email))
	<form id="deleteItem" action="{{ route('admin.settings.emails.destroy', $queryWithId) }}" method="post">
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
    <script type="text/javascript" src="{{ url('/') }}/js/tinymce/filemanager.js"></script>
@endpush
