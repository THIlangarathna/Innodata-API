<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\ImagesController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

//Login
Route::post('/login', [UsersController::class, 'login']);

//Logout
Route::middleware('auth:api')->get('/logout', [UsersController::class, 'logout']);

//View user details
Route::middleware('auth:api')->get('/user', [UsersController::class, 'index']);

//Upload zip file
Route::middleware(['auth:api','user'])->post('/zip', [ImagesController::class, 'store']);

//View list
Route::middleware(['auth:api','admin'])->get('/list', [ImagesController::class, 'index']);

//ID pattern for {id} = '[0-9]+'

//View items
Route::middleware(['auth:api','admin'])->get('/list/{id}', [ImagesController::class, 'show']);

//Delete temp folder
Route::middleware(['auth:api','admin'])->get('/close/{id}', [ImagesController::class, 'close']);