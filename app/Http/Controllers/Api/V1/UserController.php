<?php namespace App\Http\Controllers\Api\V1;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{

    /**
     * Returns the current user's info.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request)
    {
        $user = $request->user();
        $response = [
            'name'          => $user->name,
            'email'         => $user->email,
            'info'          => $user->info,
            'lang'          => $user->lang,
            'navColor'      => $user->navColor,
            'registeredOn'  => $user->created_at->toIso8601String()
        ];
        return response()->json($response);
    }

}