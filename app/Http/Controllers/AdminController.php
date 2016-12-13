<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Lang;
use Illuminate\Http\Request;

/**
* Represents an admin controller
*/
class AdminController extends Controller
{
	private $languages = ['en', 'sv', 'fi'];

	/**
	* Returns the performance data for the given surveys
	*/
	private function getPeformanceData($surveys, $is360 = false)
	{
		$numInvited = $surveys->reduce(function($total, $survey) {
			return $total + $survey->recipients()->count();
		}, 0);

		$numAnswered = $surveys->reduce(function($total, $survey) {
			return $total + $survey->recipients()->where('hasAnswered', '=', true)->count();
		}, 0);

		$numQuestions = $surveys->reduce(function($total, $survey) {
			return $total + $survey->questions()->count();
		}, 0);

		$numCandidates = 0;

		if ($is360) {
			$numCandidates = $surveys->reduce(function($total, $survey) {
				return $total + $survey->candidates()->count();
			}, 0);
		}

		$answerRatio = 0;

		if ($numInvited > 0) {
			$answerRatio = round(($numAnswered / $numInvited) * 100);
		}

		return (object)[
			'numSurveys' => $surveys->count(),
			'numInvited' => $numInvited,
			'numAnswered' => $numAnswered,
			'answerRatio' => $answerRatio,
			'numCandidates' => $numCandidates,
			'numQuestions' => $numQuestions
		];
	}

	/**
	* Returns the performance index view
	*/
	public function performanceIndex(Request $request)
	{
		$this->validate($request, [
			'startDate' => 'date_format:Y-m-d',
			'endDate' => 'date_format:Y-m-d'
		]);

		$companyFilter = $request->company;
		$startDate = $request->startDate ?: "";
		$endDate = $request->endDate ?: "";

		if ($companyFilter != null) {
			$company = \App\Models\User::where('name', '=', $companyFilter)->first();
		} else {
			$company = null;
		}

		$errorMessage = "";

		//Get the surveys
		if ($company == null) {
			if ($companyFilter != null) {
				$errorMessage = sprintf(Lang::get('performances.companyNotFound'), $companyFilter);
			}

			$surveys = \App\Models\Survey::whereRaw('1=1', []);
		} else {
			$surveys = \App\Models\Survey::where('ownerId', '=', $company->id);
		}

		$companies = \App\Models\User::where('isAdmin', '=', false);
		$users = $companies->get();
		$showNoneSurveyData = $company == null;

		//Apply date filtering
		if ($startDate != "" && $endDate != "") {
			$surveys = $surveys
				->where('startDate', '>=', \Carbon\Carbon::parse($startDate))
				->where('endDate', '<=', \Carbon\Carbon::parse($endDate));

			if ($showNoneSurveyData) {
				$companies = $companies->where('created_at', '<=', \Carbon\Carbon::parse($startDate . " 00:00:00"));
			}
		}

		$surveys = $surveys->get();

		if ($showNoneSurveyData) {
			$numCompanies = $companies->count();

			$numLicensedCompanies = $companies
				->where('isValidated', '=', true)
				->count();

			$companiesData = (object)[
				'num' => $numCompanies,
				'numLicensed' => $numLicensedCompanies
			];
		} else {
			$companiesData = null;
		}

		if ($company != null) {
			$groups = \App\Models\Group::where('ownerId', '=', $company->id)->get();
		} else {
			$groups = \App\Models\Group::all();
		}

		$numGroups = $groups->count();
		$numGroupMembers = $groups->reduce(function($total, $group) {
			return $total + $group->members()->count();
		}, 0);

		$groupsData = (object)[
			'num' => $numGroups,
			'numMembers' => $numGroupMembers
		];

		$data = [
			'global' => $this->getPeformanceData($surveys),
			'360' => $this->getPeformanceData($surveys->filter(function($survey) { return $survey->type == \App\SurveyTypes::Individual; }), true),
			'lmtt' => $this->getPeformanceData($surveys->filter(function($survey) { return $survey->type == \App\SurveyTypes::Group; })),
			'companies' => $companiesData,
			'groups' => $groupsData
		];

		return view(
			'user.admin.performance',
			compact('data', 'users', 'showNoneSurveyData', 'errorMessage', 'companyFilter', 'startDate', 'endDate'));
	}

	/**
	* Returns the roles index
	*/
	public function rolesIndex(Request $request)
	{
		$roles = \App\Models\Role::where('special', '=', false)
			->orderBy('surveyType')
			->orderBy('id')
			->get();

		return view('user.admin.roles', compact('roles'));
	}

	/**
	* Creates a new role
	*/
	public function createRole(Request $request)
	{
		$languageValidations = [];
		foreach ($this->languages as $lang) {
			$languageValidations[$lang . 'Name'] = 'required';
		}

		$this->validate($request, array_merge(['surveyType' => 'required|integer'], $languageValidations));
		$surveyType = $request->surveyType;

		if ($surveyType != \App\SurveyTypes::Individual && $surveyType != \App\SurveyTypes::Group) {
			$surveyType = 0;
		}

		$role = new \App\Models\Role;
        $role->ownerId = Auth::user()->id;
        $role->surveyType = $surveyType;
        $role->special = false;
        $role->save();

        foreach ($this->languages as $lang) {
			$name = $request[$lang . 'Name'];
            $roleName = new \App\Models\RoleName;
            $roleName->name = $name;
            $roleName->lang = $lang;
            $role->names()->save($roleName);
        }

        $role->save();

        return redirect(action('AdminController@rolesIndex'));
	}

	/**
	* Updates the given role
	*/
	public function updateRole(Request $request)
	{
		$this->validate($request, [
			'roleId' => 'required|integer'
		]);

		$role = \App\Models\Role::find($request->roleId);
		if ($role == null) {
			return response()->json([
				'success' => false
			]);
		}

        foreach ($this->languages as $lang) {
			$name = $request->names[$lang] ?: "";
            $roleName = $role->names()->where('lang', '=', $lang)->first();

			if ($name != "") {
				\App\Models\RoleName::query()
	                ->where('roleId', '=', $role->id)
	                ->where('lang', '=', $lang)
	                ->update(['name' => $name]);
			}
        }

		return response()->json([
			'success' => true
		]);
	}

	/**
	* Exports the language strings
	*/
	public function exportLanguageStrings(Request $request)
	{
		$lang = $request->lang ?: "en";

		$toExport = [
			['Surveys', Lang::get('surveys', [], $lang)],
			['Answer types', Lang::get('answertypes', [], $lang)],
			['Buttons', Lang::get('buttons', [], $lang)],
			['Company', Lang::get('company', [], $lang)],
			['Edit roles', Lang::get('editroles', [], $lang)],
			['Emails', Lang::get('emails', [], $lang)],
			['Extra questions', Lang::get('extraquestions', [], $lang)],
			['General', Lang::get('general', [], $lang)],
			['Groups', Lang::get('groups', [], $lang)],
			['Navigation', Lang::get('nav', [], $lang)],
			['Help', Lang::get('parserHelp', [], $lang)],
			['Password', Lang::get('passwords', [], $lang)],
			['Performance', Lang::get('performances', [], $lang)],
			['Questions', Lang::get('questions', [], $lang)],
			['Report', Lang::get('report', [], $lang)],
			['Roles', Lang::get('roles', [], $lang)],
			['Settings', Lang::get('settings', [], $lang)],
			['Welcome', Lang::get('welcome', [], $lang)]
		];

		$output = [];

		foreach ($toExport as $file) {
			array_push($output, ["Field name (dont translate)", $file[0]]);
			foreach ($file[1] as $name => $value) {
				array_push($output, [$name, $value]);
			}
			array_push($output, []);
		}

		return response(mb_convert_encoding(\App\CSVParser::toCSV($output), "UTF-8"))
            ->header('Content-Type', 'text/cvs; charset=utf-8')
            ->header(
                'Content-disposition',
                'attachment; data.csv"');
	}
}
