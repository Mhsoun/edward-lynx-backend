<?php

use App\User;
use App\Group;

/**
* Contains tests for groups
*/
class GroupTest extends TestCase {
    /**
    * Returns a user that is not the signed in one
    */
    private function otherUser()
    {
        return User::where('name', '=', 'Troll AB')->get()->first();
    }

    /**
    * Tests creating a group
    */
    public function testCreate()
    {
        $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        $response = $this->call('POST', '/group', $this->withCSRF(['name' => 'Test group']));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, Group::where('name', '=', 'Test group')->delete());
    }

    /**
    * Tests editing a group
    */
    public function testEditGroup()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        //Create a group
        $group = new \App\Group;
        $group->name = "Test group";
        $group->owner = $user->id;
        $group->save();

        $response = $this->call('PUT', '/group/' . $group->id, $this->withCSRF(['name' => 'More testing']));
        $this->assertEquals(200, $response->getStatusCode());

        $group = \App\Group::find($group->id);

        $this->assertEquals('More testing', $group->name);

        //Remove the group
        $group->delete();
    }

    /**
    * Tests deleting a group
    */
    public function testDeleteGroup()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        //Create a group
        $group = new \App\Group;
        $group->name = "Test group";
        $group->owner = $user->id;
        $group->save();

        $response = $this->call('DELETE', '/group/' . $group->id . '/delete', $this->withCSRF(['confirmDeletion' => 'Yes']));
        $this->assertEquals(200, $response->getStatusCode());

        //Check that the group was deleted
        $this->assertEquals(
            true, 
            \App\Group::find($group->id) == null);
    }

    /**
    * Tests adding a new recipient as a member to a group
    */ 
    public function testAddNewMember()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        //Create a group
        $group = new \App\Group;
        $group->name = "Test group";
        $group->owner = $user->id;
        $group->save();

        $response = $this->call(
            'POST',
            '/group/' . $group->id . '/member',
            $this->withCSRF(['name' => 'elo kamil', 'email' => 'elo@kamil.se']));
        $this->assertEquals(200, $response->getStatusCode());

        //Check if added
        $recipient = \App\Recipient::where('owner', '=', $user->id)
            ->where('name', '=', 'elo kamil')
            ->where('mail', '=', 'elo@kamil.se')
            ->get()->first();

        $this->assertEquals(true, $recipient != null);

        $member = \App\GroupMember::
            where('member', '=', $recipient->id)
            ->where('group', '=', $group->id)
            ->get()->first();

        $this->assertEquals(true, $member != null);

        //Remove the group & recipient
        $group->delete();
        $member->recipient->delete();
    }

    /**
    * Tests adding an existing recipient as a member to a group
    */ 
    public function testAddExistingMember()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        //Create a group
        $group = new \App\Group;
        $group->name = "Test group";
        $group->owner = $user->id;
        $group->save();

        $recipient = new \App\Recipient;
        $recipient->owner = $user->id;
        $recipient->name = "elo kamil";
        $recipient->mail = "elo@kamil.se";
        $recipient->save();

        $response = $this->call(
            'POST',
            '/group/' . $group->id . '/member',
            $this->withCSRF(['recipientId' => $recipient->id]));
        $this->assertEquals(200, $response->getStatusCode());

        //Check if added
        $member = \App\GroupMember::
            where('member', '=', $recipient->id)
            ->where('group', '=', $group->id)
            ->get()->first();

        $this->assertEquals(true, $member != null);

        //Remove the group
        $group->delete();
        $recipient->delete();
    }

    /**
    * Tests adding an existing recipient as a member to a group when the user does not own the recipient
    */ 
    public function testAddExistingMemberNotOwner()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        //Create a group
        $group = new \App\Group;
        $group->name = "Test group";
        $group->owner = $user->id;
        $group->save();

        $recipient = new \App\Recipient;
        $recipient->owner = $this->otherUser()->id;
        $recipient->name = "elo kamil";
        $recipient->mail = "elo@kamil.se";
        $recipient->save();

        $response = $this->call(
            'POST',
            '/group/' . $group->id . '/member',
            $this->withCSRF(['recipientId' => $recipient->id]));
        $this->assertEquals(500, $response->getStatusCode());

        //Check if not added
        $member = \App\GroupMember::
            where('member', '=', $recipient->id)
            ->where('group', '=', $group->id)
            ->get()->first();

        $this->assertEquals(true, $member == null);

        //Remove the group & recipient
        $group->delete();
        $recipient->delete();
    }

    /**
    * Tests deleting a group memeber
    */ 
    public function testDeleteMember()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/'); //To get a CSRF token

        //Create a group & member
        $group = new \App\Group;
        $group->name = "Test group";
        $group->owner = $user->id;
        $group->save();

        $recipient = new \App\Recipient;
        $recipient->owner = $user->id;
        $recipient->name = "elo kamil";
        $recipient->mail = "elo@kamil.se";
        $recipient->save();

        $member = new \App\GroupMember;
        $member->member = $recipient->id;
        $member->roleId = 1;
        $group->members()->save($member);

        $response = $this->call(
            'DELETE',
            '/group/' . $group->id . '/member',
            $this->withCSRF(['memberId' => $member->member]));
        $this->assertEquals(200, $response->getStatusCode());

        //Check that deleted
        $member = \App\GroupMember::
            where('member', '=', $recipient->id)
            ->where('group', '=', $group->id)
            ->get()->first();

        $this->assertEquals(true, $member == null);

        //Remove the group & recipient
        $group->delete();
        $recipient->delete();
    }
}