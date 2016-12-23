<?php namespace App\Http\Controllers\Api\V1;

use Auth;
use Hash;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;

class UserController extends Controller
{

    use SendsPasswordResetEmails;

    /**
     * Returns the current user's info.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request)
    {
        $user = $request->user();
        $response = $this->userInfo($user);
        return response()->json($response);
    }

    /**
     * Updates user information.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $this->validate($request, [
            'name'              => 'max:255',
            'info'              => 'max:255',
            'lang'              => 'in:en,fi,sv',
            'password'          => 'required_with:currentPassword|min:6',
            'currentPassword'   => 'required_with:password|min:6|same_password:' . $user->id
        ]);

        $fields = ['name', 'info', 'lang'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $user->{$field} = $request->input($field);
            }
        }

        if ($request->has('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        $response = $this->userInfo($user);
        return response()->json($response);
    }

    /**
     * Forgot Password endpoint.
     * 
     * @param  Request $request
     * @return Illuminate\Http\Response
     */
    public function forgotPassword(Request $request)
    {
        $this->validate($request, [
            'email' => 'required|email'
        ]);

        $response = $this->broker()->sendResetLink($request->only('email'));

        if ($response === Password::RESET_LINK_SENT) {
            return response('', 201);
        } else {
            return response()->json([
                'error'     => 'Bad Request',
                'message'   => trans($response)
            ], 400);
        }
    }

    /**
     * Builds an array containing user info to be returned as response.
     * 
     * @param Model $user
     * @return array
     */
    protected function userInfo($user)
    {
        return [
            'id'            => $user->id,
            'name'          => $user->name,
            'email'         => $user->email,
            'info'          => $user->info,
            'lang'          => $user->lang,
            'navColor'      => $user->navColor,
            'type'          => User::ACCESS_LEVELS[$user->access_level],
            'registeredOn'  => $user->created_at->toIso8601String()
        ];
    }

}