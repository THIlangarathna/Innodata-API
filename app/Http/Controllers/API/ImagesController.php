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
use App\Http\Resources\ImgResource;

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
        $list = ImgResource::collection(Img::all());
        return response(['List' => $list],200);
    }

    public function show($id)
    {
        $item = Img::findOrFail($id);
        $zipPath = $item->zip;

        //create temp folder
        $temp_path = $this->temp_folder($zipPath);

        //get files in the directory
        $filesInFolder = \File::files($temp_path);

        foreach($filesInFolder as $imagepath) { 
            $file = pathinfo($imagepath);
            $imagename = $file['basename'];
            $fullimagepath = $temp_path.'/'.$imagename;
            $imagearray[] = $fullimagepath;
        }

        $details = new ImgResource(Img::findOrFail($id));
        return response(['details' => $details,'images_list' => $imagearray],200);
    }

    public function close($id)
    {
        $item = Img::findOrFail($id);
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
