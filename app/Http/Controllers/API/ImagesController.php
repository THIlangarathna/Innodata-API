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
        $zipfilename = $this->filename($zipPath);

        // Make a new folder with that name
        $temp_path = 'storage/zip/temp/'.$zipfilename;
        File::makeDirectory($temp_path);

        // load zippy
        $zippy = Zippy::load();

        // Open an archive
        $archive = $zippy->open('storage/'.$zipPath);

        // Extract archive contents to `/tmp`
        $archive->extract($temp_path);

        //get files in the directory
        $filesInFolder = \File::files($temp_path);

        foreach($filesInFolder as $imagepath) { 
            $file = pathinfo($imagepath);
            $imagename = $file['basename'];
            $fullimagepath = $temp_path.'/'.$imagename;
            $imagearray[] = $fullimagepath;

            //resize images
            $this->resize($fullimagepath,$imagename);
        }

        //unset to delete
        unset($archive);

        //delete uploaded zip
        if(File::exists(public_path('storage/zip/'.$zipfilename.'.zip'))){
            File::delete(public_path('storage/zip/'.$zipfilename.'.zip'));
        }

        //Create a zip
        $archivezip = $zippy->create('storage/zip/'.$zipfilename.'.zip',$imagearray, true);

        //delete temp files
        if(File::exists(public_path('storage/zip/temp/'.$zipfilename))){
            File::deleteDirectory(public_path('storage/zip/temp/'.$zipfilename));
        }
        

        $zip = Auth::user()->images()->create([
            'category' => $validateData['category'],
            'zip' => $zipPath,
        ]);

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
