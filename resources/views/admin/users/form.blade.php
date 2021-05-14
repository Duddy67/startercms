@extends ('layouts.admin')

@section ('main')
    <form action="">
        @foreach ($fields as $attribs)
	    <x-input :attribs=$attribs />
        @endforeach
    </form>
@endsection

