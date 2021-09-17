<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Image;
use App\Models\User;
use Alchemy\Zippy\Zippy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class ImagesController extends Controller
{
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'zip' => ['required','max:15360','file','mimes:zip,rar'],
        ]);

        $zipPath = request('zip')->store('zip','public');

        $replaced = Str::replaceArray('zip/',[''], $zipPath);
        $replaced = Str::replaceArray('.zip',[''], $replaced);
        $replaced = Str::replaceArray('.rar',[''], $replaced);

        $path = 'storage/zip/temp/'.$replaced;
        File::makeDirectory($path);

        // 
        $zippy = Zippy::load();

        // Open an archive
        $archive = $zippy->open('storage/'.$zipPath);

        // Extract archive contents to `/tmp`
        $archive->extract($path);
        // 

        // $zip = Auth::user()->images()->create([
        //     'category' => $validateData['category'],
        //     'zip' => $zipPath,
        // ]);

        return $replaced;

        return response(['message' => 'File uploaded successfully.'],200);
    }
}
