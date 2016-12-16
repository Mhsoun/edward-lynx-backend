<?php namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    /**
     * Authentication endpoint
     * 
     * @param Request $request
     * @return void
     */
    public function login(Request $request)
    {
        $user = $request->user;
        $password = $request->password;
        dd($user, $password);
    }

}