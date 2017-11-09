<?php namespace App\Http\Controllers\Api\V1;

use DB;
use Auth;
use Hash;
use App\Models\User;
use App\Models\Survey;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use App\Models\InstantFeedback;
use App\Models\DevelopmentPlanGoal;
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
     * Returns a user's info.
     *
     * @param   Illuminate\Http\Request $request
     * @param   App\Models\User         $user
     * @return  App\Http\JsonHalResponse
     */
    public function show(Request $request, User $user)
    {
        return response()->jsonHal($user);
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
        $links = [
            'self'  => url('/api/v1/user')
        ];
        return response()->jsonHal($user)
                         ->withLinks($links);
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
        $toStrip = ['name', 'info', 'gender', 'department', 'city', 'country', 'role'];
        foreach ($fields as $field) {
            if ($request->has($field)) {
                $value = in_array($field, $toStrip) ? strip_tags($request->input($field)) : $request->input($field);
                $user->{$field} = $value;
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
     * @return  App\Http\JsonHalResponse
     */
    public function registerDevice(Request $request)
    {
        $this->validate($request, [
            'token'     => 'required|string',
            'deviceId'  => 'required|string|max:255'
        ]);
            
        $token = $request->token;
        $deviceId = md5($request->deviceId);
        $user = $request->user();
        $device = $user->devices()
                       ->where('deviceId', $deviceId)
                       ->first();

        // Delete the old user on the same device if another user uses it.
        // (which may have the same token) 
        UserDevice::where('token', $token)
            ->where('userId', '!=', $user->id)
            ->delete();

        if (!$device) {
            $device = $user->devices()
                           ->create([
                                'deviceId'  => $deviceId
                            ]);
        }
        $device->token = $token;
        $device->save();

        return response()->jsonHal([
            'token'     => $device->token,
            'deviceId'  => $request->deviceId
        ], 201);
    }

    /**
     * Returns user dashboard details.
     * 
     * @param  Illuminate\Http\Request $request 
     * @return App\Http\JsonHalResponse
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $reminders = $user->reminders()->splice(0, 5)->map(function($item) {
            if ($item instanceof DevelopmentPlanGoal) {
                return [
                    'id'            => $item->developmentPlan->id,
                    'type'          => 'development-plan-goal',
                    'name'          => $item->title,
                    'description'   => $item->description,
                    'due'           => $item->dueDate->toIso8601String()
                ];
            } elseif ($item instanceof InstantFeedback) {
                return [
                    'id'            => $item->id,
                    'type'          => 'instant-feedback',
                    'name'          => $item->questions()->first()->text,
                    'description'   => null,
                    'due'           => null,
                    'date'          => $item->createdAt->toIso8601String()
                ];
            } elseif ($item instanceof Survey) {
                return [
                    'id'            => $item->id,
                    'type'          => 'survey',
                    'name'          => $item->name,
                    'description'   => $item->generateDescription($item->invite->recipient, $item->invite->link),
                    'due'           => $item->endDate->toIso8601String(),
                    'key'           => $item->invite->link,
                ];
            } else {
                throw new \UnexpectedValueException('Failed to encode reminder for object with type "'. get_class($item) .'".');
            }
        })->toArray();

        return response()->jsonHal([
            'reminders'         => $reminders,
            'answerableCount'   => $user->answerableCount(),
            'developmentPlans'  => $user->developmentPlans()->latest('createdAt')->open()->get()
        ]);
    }
    
}