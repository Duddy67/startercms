<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;


class Document extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'item_type',
        'field',
        'disk_name',
        'file_name',
        'file_size',
        'content_type',
    ];


    public function upload($file, $itemType, $fieldName, $public = true)
    {
        $this->item_type = $itemType;
        $this->field = $fieldName;
        $this->disk_name = md5($file->getClientOriginalName().microtime()).'.'.$file->getClientOriginalExtension();
        $this->file_name = $file->getClientOriginalName();
        $this->file_size = $file->getSize();
        $this->content_type = $file->getMimeType();
        $this->is_public = $public;

	Storage::disk('local')->putFileAs(
	    'public',
	    $file,
	    $this->disk_name
	);

	return;
    }

    public static function getUserFiles($user = null)
    {
	$user = ($user) ? $user : auth()->user();
	$files = DB::table('documents')->where(['item_type' => 'user', 'item_id' => $user->id, 'is_public' => 1])->get();

	// Set the file url.
	foreach ($files as $key => $file) {
	    $files[$key]->url = url('/').'/storage/'.$file->disk_name;
	}

	return $files;
    }

    public static function deleteAttachedFiles($item)
    {
        $documents = [];

	foreach ($item->documents as $document) {
            $public = ($document->is_public) ? 'public/' : '';
	    $documents[] = $public.$document->disk_name;
	}

	Storage::delete($documents);
    }

    public function getUrl()
    {
        return Storage::url($this->disk_name);
    }

    public function getPath()
    {
        return Storage::path($this->disk_name);
    }
}
