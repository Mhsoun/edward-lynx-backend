<?php namespace App\Http\Controllers\Api\V1;

use Auth;
use Hash;
use App\Models\User;
use App\Models\UserDevice;
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
     * @param   Request                 $request
     * @return  App\Http\HalResponse
     */
    public function get(Request $request)
    {
        $user = $request->user();
        return response()->jsonHal($user);
    }

    /**
     * Updates user information.
     * 
     * @param   Request                 $request
     * @return  App\Http\HalResponse
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
        
        return response()->jsonHal($user);
    }

    /**
     * Forgot Password endpoint.
     * 
     * @param   Request                     $request
     * @return  Illuminate\Http\Response
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
     * Adds a device registration token to a user.
     *
     * @param   Illuminate\Http\Request $request
     * @return  Illuminate\Http\Response
     */
    public function registrationTokens(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|string'
        ]);
            
        $user = request()->user();

        $device = new UserDevice();
        $device->token = $request->token;
        
        $user->devices()->save($device);
        
        return response('', 201);

    }
    
}