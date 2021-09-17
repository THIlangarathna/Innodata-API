<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;

class UsersController extends Controller
{
    public function login(Request $request)
    {
        $login = $request->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if(!Auth::attempt($login))
        {
            return response(['message' => 'Invalid Credentials']);
        }

        $accessToken = Auth::user()->createToken('authToken')->accessToken;

        return response(['user' => Auth::user(), 'access_Token' => $accessToken],200);
    }

    public function index()
    {
        return response(['user' => Auth::user()]);
    }
}
