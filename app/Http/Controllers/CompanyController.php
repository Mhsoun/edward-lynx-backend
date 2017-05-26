<?php namespace App\Http\Controllers;

use Mail;
use App\Models\User;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Lang;
use Illuminate\Contracts\Auth\PasswordBroker;

/**
* Represents a controller for companies
*/
class CompanyController extends Controller
{
	/**
	* Returns the index view
	*/
	public function index()
	{
		$users = User::companies()
                    ->get();
		return view('user.admin.companies', compact('users'));
	}

	/**
	* Shows the create company page
	*/
	public function create()
	{
		return view('company.register');
	}

	/**
	* Stores a created company
	*/
	public function store(Request $request)
	{
		$this->validate($request, [
			'name' => 'unique:users|required',
			'email' => 'unique:users|email|required|',
			'allowedSurveyTypes' => 'integer'
		]);

		$user = new \App\Models\User;

		$user->name = $request->name;
		$user->info = $request->info != null ? $request->info : "";
		$user->email = $request->email != null ? $request->email : "";
		$user->password = "";

		$user->save();
		return redirect(action('CompanyController@index'));
	}

	/**
     * Returns the view for edting the given company
     */
    public function edit($id)
    {
        $user = User::find($id);

        if (is_null($user)) {
            return redirect(action('CompanyController@index'));
        }

        return view('company.edit', compact('user'));
    }

    /**
    * Sends a password setup link to the given user
    */
    private function sendPasswordSetupEmail($user)
    {
        $passwordSetup = new \App\Models\PasswordSetup;
        $passwordSetup->userId = $user->id;
        $passwordSetup->token = str_random(32);
        $passwordSetup->createdAt = \Carbon\Carbon::now();
        $passwordSetup->save();

        $mailData = ['token' => $passwordSetup->token];

        Mail::queue('emails.passwordLink', $mailData, function($message) use ($user) {
            $message
                ->to($user->email, $user->name)
                ->subject("Setup password");
        });
    }

    /**
     * Updates the given company
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'email'
        ]);

        $user = User::find($id);

        if ($user == null) {
            return redirect(action('CompanyController@index'));
        }

        $beforeValidated = $user->isValidated;

		$user->name = $request->name;
        $user->email = $request->email;
        $user->info = $request->info;

        $allowedSurveyTypes = [];

        foreach (\App\SurveyTypes::all() as $type) {
            $key = 'surveyType_' . $type;
            if ($request[$key] === "on") {
                array_push($allowedSurveyTypes, $type);
            }
        }

        $user->allowedSurveyTypes = \App\SurveyTypes::createInt($allowedSurveyTypes);

        if ($request->validated === 'validated') {
            $user->isValidated = true;
        } else {
            $user->isValidated = false;
        }

        $user->navColor = $request->navColor;
        $user->save();

        //Check if to send password link
        if ($user->password == "" && $user->isValidated && $user->isValidated != $beforeValidated) {
            Session::flash('changeText', Lang::get('company.passwordSent'));
            $this->sendPasswordSetupEmail($user);
        }

        //Upload logo
        $file = $request->file('file');

        if ($file != null) {
            $fileName = $user->name . '_logo.png';
            $destinationPath = public_path() . '/images/logos/';
            $file->move($destinationPath, $fileName);
        }

        return redirect()->back();
    }

    /**
    * Resets the logo for the given user
    */
    public function resetLogo(Request $request, $id)
    {
        $user = User::find($id);

        if ($user == null) {
            return redirect(action('UserController@index'));
        }

        $user->navColor = "";
        $fileName = public_path() . '/images/logos/' . $user->name . '_logo.png';
        File::delete($fileName);
        $user->save();

        return redirect()->back();
    }

    /**
     * Deletes the given company
     */
    public function destroy($id)
    {
        User::find($id)->delete();
        return redirect()->back();
    }

	/**
	* Views the projects for the given company
	*/
	public function viewProjects($id)
	{
		$user = User::find($id);

		if ($user == null) {
			return redirect(action('UserController@index'));
		}

		$activeSurveys = [];
        $finishedSurveys = [];
        $timeNow = \Carbon\Carbon::now(\App\Models\Survey::TIMEZONE);

        foreach (\App\Models\Survey::where('ownerId', '=', $user->id)->get() as $survey) {
            $survey->numCompleted = $survey->recipients()
                ->where('hasAnswered', '=', true)
                ->count();

            if ($timeNow->gt($survey->endDate)) {
                array_push($finishedSurveys, $survey);
            } else {
                array_push($activeSurveys, $survey);
            }
        }

		return view('company.projects', compact('activeSurveys', 'finishedSurveys'));
	}

	/**
	* Returns the view for resetting password
	*/
	public function resetPasswordView($id)
	{
		$user = User::find($id);
		if ($user == null) {
			return redirect(action('UserController@index'));
		}

		return view('company.resetPassword', compact('user'));
	}

	/**
	* Resets the password for a user
	*/
	public function resetPassword(Request $request, $id)
	{
		$user = User::find($id);
		if ($user == null) {
			return redirect(action('UserController@index'));
		}

		$this->validate($request, [
			'password' => 'required',
			'confirmPassword' => 'required|same:password',
		]);

		$user->password = bcrypt($request->password);
        $user->save();

		return redirect(action('CompanyController@edit', ['id' => $user->id]));
	}
}
