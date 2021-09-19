<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use App\Models\Picture;
use App\Models\User;
use Alchemy\Zippy\Zippy;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Image;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\PictureResource;
use \Illuminate\Database\Eloquent\ModelNotFoundException;

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

        //Add to DB
        $zip = Auth::user()->images()->create([
            'category' => $validateData['category'],
            'zip' => $zipPath,
        ]);

        //create temp folder
        $temp_path = $this->temp_folder($zipPath);

        // Get the file name(only the name)
        $zipfilename = $this->filename($zipPath);

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

        //delete uploaded zip
        if(File::exists(public_path('storage/zip/'.$zipfilename.'.zip'))){
            File::delete(public_path('storage/zip/'.$zipfilename.'.zip'));
        }

        // load zippy
        $zippy = Zippy::load();

        //Create a zip
        $archivezip = $zippy->create('storage/zip/'.$zipfilename.'.zip',$imagearray, true);

        //delete temp files
        if(File::exists(public_path('storage/zip/temp/'.$zipfilename))){
            File::deleteDirectory(public_path('storage/zip/temp/'.$zipfilename));
        } 

        return response(['message' => 'File uploaded successfully.'],200);
    }

    public function temp_folder($zipPath)
    {
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

        //unset to delete
        unset($archive);

        return $temp_path;
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

    public function index()
    {
        $list = PictureResource::collection(Picture::all());
        return response(['List' => $list],200);
    }

    public function show($id)
    {
        try {
            $item = Picture::findOrFail($id);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response(['message' => 'Not found'], 404);
        }
        $zipPath = $item->zip;

        // Get the file name(only the name)
        $zipfilename = $this->filename($zipPath);

        //check if file exits
        if(File::exists(public_path('storage/zip/temp/'.$zipfilename))){
            $temp_path = public_path('storage/zip/temp/'.$zipfilename);

            $imagearray = $this->imagedirectory($temp_path);
        }
        else
        {
            //create temp folder
            $temp_path = $this->temp_folder($zipPath);

            $imagearray = $this->imagedirectory($temp_path);
        }

        $details = new PictureResource(Picture::findOrFail($id));
        return response(['details' => $details,'images_list' => $imagearray],200);
    }

    public function imagedirectory($temp_path)
    {
        //get files in the directory
        $filesInFolder = \File::files($temp_path);

        $index = 0;
        $imagearray = [];

        foreach($filesInFolder as $imagepath) {

            $index = $index +1;
            $file = pathinfo($imagepath);
            $imagename = $file['basename'];
            $fullimagepath = $temp_path.'/'.$imagename;
            $newfullimagepath = strstr($fullimagepath, 'zip/temp/');
            $imagearray[] = ['id' => $index, 'path' => $newfullimagepath];
            
        }

        return $imagearray;
    }

    public function close($id)
    {
        $item = Picture::findOrFail($id);
        $zipPath = $item->zip;

        //get file name
        $zipfilename = $this->filename($zipPath);

        //delete temp files
        if(File::exists(public_path('storage/zip/temp/'.$zipfilename))){
            File::deleteDirectory(public_path('storage/zip/temp/'.$zipfilename));
        } 

        return response(['message' => 'Temp folder deleted successfully.'],200);
    }

}
