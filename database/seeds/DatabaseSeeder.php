<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Role;
use App\Models\RoleName;
use App\SurveyTypes;
use App\Models\ExtraQuestion;
use App\Models\ExtraQuestionName;
use App\Models\ExtraQuestionValue;
use App\Models\ExtraQuestionValueName;
use App\ExtraAnswerValue;

class DatabaseSeeder extends Seeder
{
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run()
	{
		$this->call('UserTableSeeder');
	}
}

class UserTableSeeder extends Seeder
{
    /**
    * Creates a role
    */
    private function createRole($owner, $names, $type, $special = false)
    {
        $role = new Role;
        $role->ownerId = $owner->id;
        $role->surveyType = $type;
        $role->special = $special;
        $role->save();

        foreach ($names as $lang => $name) {
            $roleName = new RoleName;
            $roleName->name = $name;
            $roleName->lang = $lang;
            $role->names()->save($roleName);
        }

        $role->save();
    }

    /**
    * Adds a language to to the given role
    */
    private function addLanguage($owner, $englishName, $name, $lang, $type)
    {
        $role = null;

        foreach (RoleName::where('name', '=', $englishName)->get() as $roleName) {
            $currentRole = Role::find($roleName->roleId);

            if ($currentRole->surveyType == $type) {
                $role = $currentRole;
                break;
            }
        }

        if ($role == null) {
            dd([$englishName, $type]);
        }

        $roleName = new RoleName;
        $roleName->name = $name;
        $roleName->lang = $lang;
        $role->names()->save($roleName);

        $role->save();
    }

    /**
    * Creates the default roles
    */
    private function createDefaultRoles()
    {
        $owner = User::where('name', '=', 'Edward Lynx')->first();

        //360 roles
        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.self', [], 'en'), 'sv' => Lang::get('roles.self', [], 'sv')],
            SurveyTypes::Individual,
            true);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.colleague', [], 'en'), 'sv' => Lang::get('roles.colleague', [], 'sv')],
            SurveyTypes::Individual);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.manager', [], 'en'), 'sv' => Lang::get('roles.manager', [], 'sv')],
            SurveyTypes::Individual);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.customer', [], 'en'), 'sv' => Lang::get('roles.customer', [], 'sv')],
            SurveyTypes::Individual);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.matrixManager', [], 'en'), 'sv' => Lang::get('roles.matrixManager', [], 'sv')],
            SurveyTypes::Individual);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.otherStakeholder', [], 'en'), 'sv' => Lang::get('roles.otherStakeholder', [], 'sv')],
            SurveyTypes::Individual);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.directReport', [], 'en'), 'sv' => Lang::get('roles.directReport', [], 'sv')],
            SurveyTypes::Individual);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.otherCandidates', [], 'en'), 'sv' => Lang::get('roles.otherCandidates', [], 'sv')],
            SurveyTypes::Individual,
            true);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.candidates', [], 'en'), 'sv' => Lang::get('roles.candidates', [], 'sv')],
            SurveyTypes::Individual,
            true);

        //LMTT roles
        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.manager', [], 'en'), 'sv' => Lang::get('roles.manager', [], 'sv')],
            SurveyTypes::Group);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.managementTeam', [], 'en'), 'sv' => Lang::get('roles.managementTeam', [], 'sv')],
            SurveyTypes::Group);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.teamMember', [], 'en'), 'sv' => Lang::get('roles.teamMember', [], 'sv')],
            SurveyTypes::Group);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.reportingManager', [], 'en'), 'sv' => Lang::get('roles.reportingManager', [], 'sv')],
            SurveyTypes::Group);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.otherStakeholder', [], 'en'), 'sv' => Lang::get('roles.otherStakeholder', [], 'sv')],
            SurveyTypes::Group);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.customer', [], 'en'), 'sv' => Lang::get('roles.customer', [], 'sv')],
            SurveyTypes::Group);

        $this->createRole(
            $owner,
            ['en' => Lang::get('roles.directReport', [], 'en'), 'sv' => Lang::get('roles.directReport', [], 'sv')],
            SurveyTypes::Group);
    }

    /**
    * Creates the finnish roles
    */
    private function createFinnishRoles()
    {
        $owner =  User::where('name', '=', 'Edward Lynx')->first();

        //360 roles
        $this->addLanguage(
            $owner,
            Lang::get('roles.self', [], 'en'),
            Lang::get('roles.self', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.colleague', [], 'en'),
            Lang::get('roles.colleague', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.manager', [], 'en'),
            Lang::get('roles.manager', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.customer', [], 'en'),
            Lang::get('roles.customer', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.matrixManager', [], 'en'),
            Lang::get('roles.matrixManager', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.otherStakeholder', [], 'en'),
            Lang::get('roles.otherStakeholder', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.directReport', [], 'en'),
            Lang::get('roles.directReport', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.otherCandidates', [], 'en'),
            Lang::get('roles.otherCandidates', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        $this->addLanguage(
            $owner,
            Lang::get('roles.candidates', [], 'en'),
            Lang::get('roles.candidates', [], 'fi'),
            'fi',
            SurveyTypes::Individual);

        //LMTT roles
        $this->addLanguage(
            $owner,
            Lang::get('roles.manager', [], 'en'),
            Lang::get('roles.manager', [], 'fi'),
            'fi',
            SurveyTypes::Group);

        $this->addLanguage(
            $owner,
            Lang::get('roles.managementTeam', [], 'en'),
            Lang::get('roles.managementTeam', [], 'fi'),
            'fi',
            SurveyTypes::Group);

        $this->addLanguage(
            $owner,
            Lang::get('roles.teamMember', [], 'en'),
            Lang::get('roles.teamMember', [], 'fi'),
            'fi',
            SurveyTypes::Group);

        $this->addLanguage(
            $owner,
            Lang::get('roles.reportingManager', [], 'en'),
            Lang::get('roles.reportingManager', [], 'fi'),
            'fi',
            SurveyTypes::Group);

        $this->addLanguage(
            $owner,
            Lang::get('roles.customer', [], 'en'),
            Lang::get('roles.customer', [], 'fi'),
            'fi',
            SurveyTypes::Group);

        $this->addLanguage(
            $owner,
            Lang::get('roles.directReport', [], 'en'),
            Lang::get('roles.directReport', [], 'fi'),
            'fi',
            SurveyTypes::Group);
    }

    /**
    * Creates the given extra question
    */
    private function createExtraQuestion($owner, $type, $isOptional, $names, $values)
    {
        $extraQuestion = new ExtraQuestion;
        if ($owner != null) {
            $extraQuestion->ownerId = $owner->id;
        }

        $extraQuestion->type = $type;
        $extraQuestion->isOptional = $isOptional;
        $extraQuestion->save();

        //Create the names
        foreach ($names as $lang => $name) {
            $questionName = new ExtraQuestionName;
            $questionName->name = $name;
            $questionName->lang = $lang;
            $extraQuestion->names()->save($questionName);
        }

        //Create the values
        foreach ($values as $value) {
            $questionValue = new ExtraQuestionValue;
            $extraQuestion->values()->save($questionValue);

            foreach ($value as $lang => $name) {
                $questionNameValue = new ExtraQuestionValueName;
                $questionNameValue->name = $name;
                $questionNameValue->lang = $lang;
                $questionValue->names()->save($questionNameValue);
            }
        }
    }

    /**
    * Returns the language strings in all the given languages
    */
    private function getLanguageStringInLanguages($name, $langs)
    {
        $strings = [];

        foreach ($langs as $lang) {
            $strings[$lang] = Lang::get($name, [], $lang);
        }

        return $strings;
    }

    /**
    * Returns the given values in all languages
    */
    private function getValues($valueNames, $langs)
    {
        $values = [];

        foreach ($valueNames as $value) {
            array_push($values, $this->getLanguageStringInLanguages($value, $langs));
        }

        return $values;
    }

    /**
    * Creates a new value
    */
    private function createValue($createFn, $langs)
    {
        $value = [];

        foreach ($langs as $lang) {
            $value[$lang] = $createFn(function($str) use ($lang) {
                return Lang::get($str, [], $lang);
            });
        }

        return $value;
    }

    /**
    * Creates the default extra questions
    */
    private function createExtraQuestions()
    {
        $langs = ['en', 'sv', 'fi'];

        $this->createExtraQuestion(
            null, ExtraAnswerValue::Options, false,
            $this->getLanguageStringInLanguages('extraquestions.gender', $langs),
            $this->getValues(['extraquestions.male', 'extraquestions.female'], $langs));

        $this->createExtraQuestion(
            null, ExtraAnswerValue::Text, false,
            $this->getLanguageStringInLanguages('surveys.recipientPosition', $langs),
            []);

        $this->createExtraQuestion(
            null, ExtraAnswerValue::Date, true,
            $this->getLanguageStringInLanguages('extraquestions.dateOfBirth', $langs),
            []);

        $this->createExtraQuestion(
            null, ExtraAnswerValue::Options, false,
            $this->getLanguageStringInLanguages('extraquestions.howLongWorking', $langs), [
                $this->createValue(function($getLangFn) {
                    return "0-12 " . $getLangFn('extraquestions.months');
                }, $langs),
                $this->createValue(function($getLangFn) {
                    return "1-3 " . $getLangFn('extraquestions.years');
                }, $langs),
                $this->createValue(function($getLangFn) {
                    return "4-5 " . $getLangFn('extraquestions.years');
                }, $langs),
                $this->createValue(function($getLangFn) {
                    return "5-8 " . $getLangFn('extraquestions.years');
                }, $langs),
                $this->createValue(function($getLangFn) {
                    return $getLangFn('extraquestions.moreThan') . " 8 " . $getLangFn('extraquestions.years');
                }, $langs)
            ]);
    }

    public function run()
    {
        if (User::where('name', '=', 'Edward Lynx')->get()->count() == 0) {
        	$edwardLynx = User::create([
        		'name' 		    => 'Edward Lynx',
        		'email' 	    => 'admin@edwardlynx.com',
        		'info' 		    => '',
        		'password' 	    => Hash::make("password123"),
                'access_level'  => 0
        	]);

        	$edwardLynx->isAdmin = true;
        	$edwardLynx->isValidated = true;
        	$edwardLynx->save();
        }

        //Add default roles
        if (Role::all()->count() == 0) {
            $this->createDefaultRoles();
        }

        //Add finnish roles
        if (RoleName::where('lang', '=', 'fi')->count() == 0) {
            $this->createFinnishRoles();
        }

        //Add extra questions
        if (ExtraQuestion::all()->count() == 0) {
            $this->createExtraQuestions();
        }
    }
}
