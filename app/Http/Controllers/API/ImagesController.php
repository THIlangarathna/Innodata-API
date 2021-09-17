<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Img;
use App\Models\User;
use Alchemy\Zippy\Zippy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Image;
use Illuminate\Support\Facades\Storage;

class ImagesController extends Controller
{
    public function store(Request $request)
    {
        // Validate data
        $validateData = $request->validate([
            'category' => ['required', 'string', 'max:255'],
            'zip' => ['required','max:15360','file','mimes:zip'],
        ]);

        //Store Zip
        $zipPath = request('zip')->store('zip','public');

        // Get the file name(only the name)
        $replaced = $this->filename($zipPath);

        // Make a new folder with that name
        $path = 'storage/zip/temp/'.$replaced;
        File::makeDirectory($path);

        // load zippy
        $zippy = Zippy::load();

        // Open an archive
        $archive = $zippy->open('storage/'.$zipPath);

        // Extract archive contents to `/tmp`
        $archive->extract($path);

        //get files in the directory
        $filesInFolder = \File::files($path);

        foreach($filesInFolder as $imagepath) { 
            $file = pathinfo($imagepath);
            $imagename = $file['basename'];
            $fullimagepath = $path.'/'.$imagename;
            $imagearray[] = $fullimagepath;

            //resize images
            $this->resize($fullimagepath,$imagename);
        }

        //delete zip
        $deletezip = public_path('storage/zip/'.$replaced.'.zip');

        unset($archive);

        //delete uploaded zip
        if(File::exists($deletezip)){
            unlink($deletezip);
        }

        //Create a zip
        $archivezip = $zippy->create('storage/zip/'.$replaced.'.zip',$imagearray, true);

        // $zip = Auth::user()->images()->create([
        //     'category' => $validateData['category'],
        //     'zip' => $zipPath,
        // ]);

        return response(['message' => 'File uploaded successfully.'],200);
    }

    public function filename($path)
    {
        $replaced = Str::replaceArray('zip/',[''], $path);
        $replaced = Str::replaceArray('.zip',[''], $replaced);

        return $replaced;
    }

    public function resize($imagepath,$imagename)
    {   
        $folderpath = Str::replaceArray($imagename,[''], $imagepath);
        $destinationPath = $folderpath;

        $imgFile = Image::make($imagepath);

        $imgFile->resize('',200, function ($constraint) {
		    $constraint->aspectRatio();
		})->save($destinationPath.'/'.$imagename);
    }

}
