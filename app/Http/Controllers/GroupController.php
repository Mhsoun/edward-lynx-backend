<?php namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Validator;

/**
* Representets a controller for groups.
* All actions in this controller are AJAX calls.
*/
class GroupController extends Controller
{
    /**
    * Returns the company id for the given request
    */
    private function getCompanyId(Request $request)
    {
       if (Auth::user()->isAdmin && $request->companyId != null) {
            return $request->companyId;
        } else {
            return Auth::user()->id;
        }
    }

    /**
    * Stores a group
    */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);


        $group = new \App\Models\Group;
        $group->name = $request->name;
        $group->ownerId = $this->getCompanyId($request);

        $subcompanyName = null;

        if ($request->subcompanyId != null) {
            $subcompany = \App\Models\Group::find($request->subcompanyId);

            if ($subcompany == null) {
                return response()->json([
                    'success' => false
                ]);
            }

            $subcompanyName = $subcompany->name;
            $subcompany->subgroups()->save($group);
        } else {
            $group->save();
        }

        return response()->json([
            'success' => true,
            'id' => $group->id,
            'name' => $group->name,
            'subcompanyName' => $subcompanyName
        ]);
    }

    /**
    * Stores a subcompany
    */
    public function storeSubcompany(Request $request)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $group = new \App\Models\Group;
        $group->name = $request->name;
        $group->ownerId = $this->getCompanyId($request);
        $group->isSubcompany = true;
        $group->save();

        return response()->json([
            'success' => true,
            'id' => $group->id
        ]);
    }

    /**
    * Creates a new group member
    */
    private function createGroupMember($group, $recipientId)
    {
        $newMember = null;

        //Add only recipient if not already added
        if ($group->members()->where('memberId', '=', $recipientId)->first() == null) {
            $member = new \App\Models\GroupMember;
            $member->memberId = $recipientId;
            $member->roleId = \App\Roles::getRoleIdByName('roles.manager', \App\SurveyTypes::Group);
            $group->members()->save($member);
            $newMember = $member;
        }

        //Add the recipient to the parent group
        if ($group->parentGroup != null) {
            $this->createGroupMember($group->parentGroup, $recipientId);
        }

        return $newMember;
    }

    /**
    * Creates a new member
    */
    private function createNewMember($group, $owner, $name, $email, $position)
    {
        $recipient = \App\Models\Recipient::make($owner, $name, $email, $position);
        return $this->createGroupMember($group, $recipient->id);
    }

    /**
    * Creates a new group member
    */
    public function storeMember(Request $request, $id)
    {
        $group = \App\Models\Group::findOrFail($id);

        //Create a new recipient
        if ($request->name != null && $request->email != null) {
            $this->validate($request, [
                'email' => 'email'
            ]);

            $member = $this->createNewMember(
                $group,
                $this->getCompanyId($request),
                $request->name,
                $request->email,
                $request->position);

            if ($member != null) {
                return response()->json([
                    'success' => true,
                    'memberId' => $member->memberId,
                    'roleId' => $member->roleId
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'A participant already exists with the given email.'
                ]);
            }
        }

        //From existing one
        if ($request->recipientId != null) {
            $recipient = \App\Models\Recipient::find($request->recipientId);

            if ($recipient != null) {
                if (!($recipient->ownerId == Auth::user()->id || Auth::user()->isAdmin)) {
                    return response()->json([
                        'success' => false
                    ], $status = 500);
                }

                $member = $this->createGroupMember($group, $recipient->id);

                if ($member != null) {
                    return response()->json([
                        'success' => true,
                        'roleId' => $member->roleId,
                        'memberId' => $member->memberId,
                        'name' => $recipient->name,
                        'email' => $recipient->mail,
                        'position' => $recipient->position,
                    ]);
                } else {
                    return response()->json([
                        'success' => false
                    ]);
                }
            }
        }

        return response()->json([
            'success' => false
        ]);
    }

    /**
    * Imports members from a CSV file
    */
    public function importMembers(Request $request, $id)
    {
        $this->validate($request, [
            'csv' => 'required'
        ]);

        $group = \App\Models\Group::findOrFail($id);

        $created = [];

        //Parse and create members
        foreach (\App\CSVParser::parse($request->csv, 3) as $user) {
            $validator = Validator::make(['email' => $user[1]], [
                'email' => 'email'
            ]);

            if (!$validator->fails()) {
                $member = $this->createNewMember(
                    $group,
                    $this->getCompanyId($request),
                    $user[0], $user[1], $user[2]);

                if ($member != null) {
                    array_push($created, (object)[
                        'name' => $user[0],
                        'email' => $user[1],
                        'position' => $user[2],
                        'memberId' => $member->memberId,
                        'roleId' => $member->roleId
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'created' => $created
        ]);
    }

    /**
    * Updates the given member in the given group
    */
    public function updateMember(Request $request, $id)
    {
        $this->validate($request, [
            'memberId' => 'required|integer',
            'roleId' => 'required|integer'
        ]);

        $group = \App\Models\Group::find($id);

        if ($group == null) {
            return response()->json([
                'success' => false
            ]);
        }

        $groupMember = $group->members()
            ->where('memberId', '=', $request->memberId)
            ->first();

        if ($groupMember == null) {
            return response()->json([
                'success' => false
            ]);
        }

        if (\App\Roles::valid($request->roleId)) {
            //As Laravel does not support composite primary keys, update raw.
            \App\Models\GroupMember::query()
                ->where('groupId', '=', $group->id)
                ->where('memberId', '=', $groupMember->memberId)
                ->update(['roleId' => $request->roleId]);

            return response()->json([
                'success' => true
            ]);
        } else {
            return response()->json([
                'success' => false
            ]);
        }
    }

    /**
    * Removes a member from a group
    */
    public function destroyMember(Request $request, $id)
    {
        $this->validate($request, [
            'memberId' => 'required|integer'
        ]);

        $group = \App\Models\Group::findOrFail($id);

        $removed = \App\Models\GroupMember
            ::where('groupId', '=', $id)
            ->where('memberId', '=', $request->memberId)
            ->delete() > 0;

        //Remove from subgroups
        foreach ($group->subgroups as $subgroup) {
            \App\Models\GroupMember
                ::where('groupId', '=', $subgroup->id)
                ->where('memberId', '=', $request->memberId)
                ->delete();
        }

        return response()->json([
            'success' => $removed
        ], $status = ($removed ? 200 : 404));
    }

    /**
     * Update the specified group in storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required'
        ]);

        $group = \App\Models\Group::findOrFail($id);

        //Update the name
        $group->name = $request->name;
        $group->save();

        return response()->json([
            'success' => true
        ]);
    }

    /**
     * Remove the specified group from storage.
     *
     * @param  int  $id
     * @return Response
     */
    public function destroy(Request $request, $id)
    {
        $delete = $request->confirmDeletion == "Yes";
        $group = \App\Models\Group::findOrFail($id);

        return response()->json([
            'success' => $group->delete() > 0
        ]);
    }
}
