<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;


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


    public function upload($file, $itemType, $fieldName)
    {
        $this->item_type = $itemType;
        $this->field = $fieldName;
        $this->disk_name = md5($file->getClientOriginalName().microtime()).'.'.$file->getClientOriginalExtension();
        $this->file_name = $file->getClientOriginalName();
        $this->file_size = $file->getSize();
        $this->content_type = $file->getMimeType();

	Storage::disk('local')->putFileAs(
	    'public',
	    $file,
	    $this->disk_name
	);

	return;
    }

    public static function deleteRelatedFiles($item)
    {
	$documents = $item->documents()->pluck('disk_name')->toArray();
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
