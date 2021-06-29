<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;

class DocumentController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the document list.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        $files = Document::getUserFiles();

        return view('documents.list', compact('files'));
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('upload') && $request->file('upload')->isValid()) {
	    $document = new Document;
	    $document->upload($request->file('upload'), 'user', 'upload');
	    auth()->user()->documents()->save($document);
	}

        file_put_contents('debog_file.txt', print_r($request->all(), true));
	return redirect()->route('document')->with('success', __('messages.users.update_success'));
    }
}
