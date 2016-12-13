<?php namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\Registrar;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Http\Request;

/**
* Represents an auth controller 
*/
class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers;

    /**
     * Create a new authentication controller instance.
     *
     * @param  \Illuminate\Contracts\Auth\Guard $auth
     * @param  \Illuminate\Contracts\Auth\Registrar $registrar
     * @return void
     */
    public function __construct(Guard $auth, Registrar $registrar)
    {
        $this->auth = $auth;
        $this->registrar = $registrar;
        $this->middleware('guest', ['except' => 'getLogout']);
    }

    public function getRegister()
    {
        abort(403, 'Unauthorized action.');
    }

    public function postRegister(Request $request)
    {
        abort(403, 'Unauthorized action.');
    }

    public function postLogin(Request $request) {
        $user = \App\Models\User::where('email', $request->email)->first();

        if ($user == null) {
            return redirect($this->loginPath())
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => $this->getFailedLoginMessage(),
                ]);
        }

        if ($user->isValidated) {
            $credentials = $request->only('email', 'password');

            if ($this->auth->attempt($credentials, $request->has('remember'))) {
                return redirect()->intended($this->redirectPath());
            }

            return redirect($this->loginPath())
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => $this->getFailedLoginMessage(),
                ]);
        } else {
            abort(403,'Unauthorized action.');
        }
    }
}
