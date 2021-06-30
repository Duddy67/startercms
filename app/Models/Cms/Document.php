<?php

namespace App\Models\Cms;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\Settings\General;


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

    public function getItems($request)
    {
        $perPage = $request->input('per_page', General::getGeneralValue('pagination', 'per_page'));
        $search = $request->input('search', null);
        $sortedBy = $request->input('sorted_by', null);
        $types = $request->input('types', []);

	$query = Document::query();
	$query->where(['item_type' => 'user', 'item_id' => auth()->user()->id, 'is_public' => 1]);

	if ($search !== null) {
	    $query->where('file_name', 'like', '%'.$search.'%');
	}

	if (!empty($types)) {
	    $query->where('content_type', 'regexp', '^('.implode('|', $types).')');
	}

	if ($sortedBy !== null) {
	    preg_match('#^([a-z0-9_]+)_(asc|desc)$#', $sortedBy, $matches);
	    $query->orderBy($matches[1], $matches[2]);
	}

        $items = $query->paginate($perPage);

	// Set the file url.
	foreach ($items as $key => $item) {
	    $items[$key]->url = url('/').'/storage/'.$item->disk_name;
	}

	return $items;
    }

    public function getTypesOptions()
    {
        $types = ['image', 'application', 'audio', 'video', 'text', 'font'];
	$options = [];

	foreach ($types as $type) {
	    $options[] = ['value' => $type, 'text' => $type];
	}

	return $options;
    }

    /*public static function getUserFiles($user = null)
    {
	$user = ($user) ? $user : auth()->user();
	$files = DB::table('documents')->where(['item_type' => 'user', 'item_id' => $user->id, 'is_public' => 1])->get();

	// Set the file url.
	foreach ($files as $key => $file) {
	    $files[$key]->url = url('/').'/storage/'.$file->disk_name;
	}

	return $files;
    }*/

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
