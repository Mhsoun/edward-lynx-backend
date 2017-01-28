<?php namespace App\Http\Controllers\Api\V1;

use DB;
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
     * Return list of all users.
     *
     * @param   Illuminate\Http\Request    $request
     * @return  App\Htttp\JsonHalResponse
     */
    public function index(Request $request)
    {
        $users = $request->user()->colleagues();
        if ($request->type === 'list') {
            $resp = [];
            foreach ($users as $user) {
                $resp[] = [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email
                ];
            }
        } else {
            $resp = $users;
        }
        return response()->jsonHal($resp);
    }

    /**
     * Returns the current user's info.
     * 
     * @param   Request                 $request
     * @return  App\Http\JsonHalResponse
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
     * @return  App\Http\JsonHalResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();
        
        $this->validate($request, [
            'name'              => 'max:255',
            'info'              => 'max:255',
            'lang'              => 'in:en,fi,sv',
            'password'          => 'required_with:currentPassword|min:6',
            'currentPassword'   => 'required_with:password|min:6|same_password:' . $user->id,
            'gender'            => 'max:255',
            'department'        => 'max:255',
            'city'              => 'max:255',
            'country'           => 'max:255',
            'role'              => 'max:255'
        ]);

        $fields = ['name', 'info', 'lang', 'gender', 'department', 'city', 'country', 'role'];
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
    public function registerDevice(Request $request)
    {
        $this->validate($request, [
            'token' => 'required|string'
        ]);
            
        $user = request()->user();
        $user->devices()->firstOrCreate([
            'token' => $request->token
        ]);
        
        return response('', 201);
    }
    
}