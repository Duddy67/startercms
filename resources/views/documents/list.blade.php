<!-- Modal -->
<link rel="stylesheet" href="{{ url('/') }}/vendor/bootstrap-4.5.3/css/bootstrap.min.css">
<div class="container-fluid">
    @include ('layouts.flash-message')

    <ul class="list-group">
	@foreach ($files as $file) 
	    <li class="list-group-item">
		<a href="#" onClick="selectFile(this);" data-disk-name="{{ $file->disk_name }}" data-file-name="{{ $file->file_name }}" data-file-url="{{ $file->url }}">{{ $file->file_name }}</a>
	    </li>
	@endforeach
    </ul>

    <form method="post" action="{{ route('document') }}" id="itemForm" enctype="multipart/form-data">
	@csrf
	@method('post')
	<input type="file" name="upload">
	<input type="submit" value="Upload file">
    </form>
</div>

<script>
function selectFile(element)
{
    //alert(element.dataset.diskName);
    var value = {
	disk_name: element.dataset.diskName,
	file_name: element.dataset.fileName,
	file_url: element.dataset.fileUrl
    };

    window.parent.postMessage({
        mceAction: 'execCommand',
	cmd: 'iframeCommand',
	value
    }, origin);
}
</script>
