<?php namespace App;

use Lang;

/**
* Defines the roles
*/
abstract class Roles
{
    private static $roles = null;

    /**
    * Returns the names of the given surveys
    */
    private static function getNames($roles)
    {
        return $roles->map(function($role) {
            return $role->name();
        });
    }

    /**
    * Returns the 360 roles
    */
    public static function get360()
    {
        $roles = \App\Models\Role::where('surveyType', '=', \App\SurveyTypes::Individual)
            ->where('special', '=', false)
            ->get();

        return $roles;
    }

    /**
    * Returns the LMTT roles
    */
    public static function getLMTT()
    {
        $roles = \App\Models\Role::where('surveyType', '=', \App\SurveyTypes::Group)
            ->where('special', '=', false)
            ->get();

        return $roles;
    }

    /**
    * Returns the roles for the given survey type
    */
    public static function getForType($type)
    {
        //Progress type use 360 types
        if ($type == \App\SurveyTypes::Progress) {
            $type = \App\SurveyTypes::Individual;
        }

        //LTT use LMTT types
        if ($type == \App\SurveyTypes::LTT) {
            $type = \App\SurveyTypes::Group;
        }

        if ($type == \App\SurveyTypes::Individual) {
            return Roles::get360();
        } else if ($type == \App\SurveyTypes::Group) {
            return Roles::getLMTT();
        } else {
            return [];
        }
    }

    /**
    * Returns all the roles
    */
    public static function all()
    {
        return \App\Models\Role::all();
    }

    /**
    * Returns the name for the given role id
    */
    public static function name($roleId)
    {
        if ($roleId == -1) {
            return Lang::get('roles.others');
        }

        $role = \App\Models\Role::find($roleId);

        if ($role != null) {
            return $role->name();
        } else {
            return '';
        }
    }

    /**
    * Returns the role id given a role name id
    */
    public static function getRoleIdByName($nameId, $type)
    {
        //Progress type use 360 types
        if ($type == \App\SurveyTypes::Progress) {
            $type = \App\SurveyTypes::Individual;
        }

        //LTT use LMTT types
        if ($type == \App\SurveyTypes::LTT) {
            $type = \App\SurveyTypes::Group;
        }

        $name = \App\Models\RoleName::where('name', '=', Lang::get($nameId, [], 'en'))->get()->filter(function($roleName) use ($type) {
            return $roleName->role->surveyType == $type;
        })->first();

        return $name->roleId;
    }

    /**
    * Returns the self role id
    */
    public static function selfRoleId()
    {
        $name = \App\Models\RoleName::where('name', '=', Lang::get('roles.self', [], 'en'))->first();
        return $name->roleId;
    }

    /**
    * Returns the other candidates role id
    */
    public static function otherCandidatesRoleId()
    {
        $name = \App\Models\RoleName::where('name', '=', Lang::get('roles.otherCandidates', [], 'en'))->first();
        return $name->roleId;
    }

    /**
    * Returns the candidates role id
    */
    public static function candidatesRoleId()
    {
        $name = \App\Models\RoleName::where('name', '=', Lang::get('roles.candidates', [], 'en'))->first();
        return $name->roleId;
    }

    /**
    * Indicates if the given role value is valid
    */
    public static function valid($roleId)
    {
        return \App\Models\Role::find($roleId) != null;
    }
}
