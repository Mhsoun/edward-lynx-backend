<?php

use App\User;
use App\Group;
use App\Survey;
use App\SurveyEmailer;

/**
* Contains tests for surveys
*/
class SurveyTest extends TestCase {
    /**
    * Asserts that the given survey contains the given recipients
    */
    private function assertRecipients($survey, $recipients)
    {
        $this->assertEquals(count($recipients[0]), $survey->recipients()->count());

        for ($i = 0; $i < count($recipients[0]); $i++) { 
            $name = $recipients[0][$i];
            $email = $recipients[1][$i];
            $position = $recipients[2][$i];

            $recipient = \App\Recipient::
                where('name', '=', $name)
                ->where('mail', '=', $email)
                ->where('position', '=', $position)
                ->first();

            $this->assertNotNull($recipient);
            $this->assertNotNull($survey->recipients()
                ->where('recipientId', '=', $recipient->id)
                ->first());
        }
    }

    /**
    * Asserts that the given survey contains the given candidates
    */
    private function assertCandidates($survey, $recipients)
    {
        $this->assertEquals(count($recipients[0]), $survey->recipients()->count());

        for ($i = 0; $i < count($recipients[0]); $i++) { 
            $name = $recipients[0][$i];
            $email = $recipients[1][$i];
            $position = $recipients[2][$i];

            $recipient = \App\Recipient::
                where('name', '=', $name)
                ->where('mail', '=', $email)
                ->where('position', '=', $position)
                ->first();

            $this->assertNotNull($recipient);
            $this->assertNotNull($survey->candidates()
                ->where('recipientId', '=', $recipient->id)
                ->first());
        }
    }

    /**
    * Creates a group member for the given group
    */
    private function createGroupMember($group, $roleId, $recipientName, $recipientEmail)
    {
        $recipient = new \App\Recipient;
        $recipient->owner = $group->owner;
        $recipient->name = $recipientName;
        $recipient->mail = $recipientEmail;
        $recipient->save();

        $member = new \App\GroupMember;
        $member->member = $recipient->id;
        $member->roleId = $roleId;
        $group->members()->save($member);

        return $recipient->id;
    }

    /**
    * Tests creating a 360 survey
    */
    public function test360Create()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/survey/create');
        $this->assertEquals(200, $response->getStatusCode());

        $testPersons = [
            ['Test Testsson', 'Elo Kamil'],
            ['test@example.com', 'elo@kamil.se'],
            ['CEO', '']
        ];

        SurveyEmailer::disableMail();
        $response = $this->call('POST', '/survey/create', $this->withCSRF([
            'name' => 'Test',
            'language' => 'sv',
            'type' => 'individual',
            'startDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->toDateString(),
            'endDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->addWeek()->toDateString(),
            'description' => 'Lorem',
            'invitationSubject' => 'Invite subject',
            'invitationText' => 'Invite text',
            'reminderSubject' => 'Reminder subject',
            'reminderText' => 'Reminder text',
            'toEvaluateInvitationSubject' => 'To evaluate invitation subject',
            'toEvaluateInvitationText' => 'To evaluate invitation text',
            'candidateNames' => $testPersons[0],
            'candidateEmails' => $testPersons[1],
            'candidatePositions' => $testPersons[2]
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        SurveyEmailer::enableMail();

        //Check if created
        $survey = Survey::where('ownerId', '=', $user->id)->get()->first();
        $this->assertEquals(true, $survey != null);

        $this->assertEquals(\App\SurveyTypes::Individual, $survey->type);
        $this->assertRecipients($survey, $testPersons);
        $this->assertCandidates($survey, $testPersons);

        $survey->delete();
    }

    /**
    * Tests creating a LMTT survey
    */
    public function testLMTTCreate()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/survey/create');
        $this->assertEquals(200, $response->getStatusCode());

        //Create a group and recipients
        $group = new \App\Group;
        $group->name = "Group";
        $group->owner = $user->id;
        $group->save();

        $toEvaluateRoleId = \App\RoleName::where('name', '=', Lang::get('roles.manager', [], 'en'))
            ->first()
            ->roleId;

        $evaluatingRole1 = \App\RoleName::where('name', '=', Lang::get('roles.managementTeam', [], 'en'))
            ->first()
            ->roleId;

        $evaluatingRole2 = \App\RoleName::where('name', '=', Lang::get('roles.teamMember', [], 'en'))
            ->first()
            ->roleId;

        $includedMembers = [];

        array_push($includedMembers, $this->createGroupMember($group, $toEvaluateRoleId, "person 1", "person1@example.com"));
        array_push($includedMembers, $this->createGroupMember($group, $toEvaluateRoleId, "person 2", "person2@example.com"));
        array_push($includedMembers, $this->createGroupMember($group, $evaluatingRole1, "person 3", "person3@example.com"));
        array_push($includedMembers, $this->createGroupMember($group, $evaluatingRole2, "person 4", "person4@example.com"));
        
        SurveyEmailer::disableMail();
        $response = $this->call('POST', '/survey/create', $this->withCSRF([
            'name' => 'Test',
            'language' => 'sv',
            'type' => 'group',
            'startDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->toDateString(),
            'endDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->addWeek()->toDateString(),
            'description' => 'Lorem',
            'invitationSubject' => 'Invite subject',
            'invitationText' => 'Invite text',
            'reminderSubject' => 'Reminder subject',
            'reminderText' => 'Reminder text',
            'targetGroupId' => $group->id,
            'toEvaluateRole' => $toEvaluateRoleId,
            'includedMembers' => $includedMembers
        ]));

        $this->assertEquals(200, $response->getStatusCode());
        SurveyEmailer::enableMail();

        //Check if created
        $survey = Survey::where('ownerId', '=', $user->id)->get()->first();
        $this->assertEquals(true, $survey != null);

        $this->assertEquals(\App\SurveyTypes::Group, $survey->type);
        $this->assertEquals($group->id, $survey->targetGroupId);
        $this->assertEquals(3, count($survey->roleGroups));
        $this->assertEquals($toEvaluateRoleId, $survey->roleGroups[0]->roleId);
        $this->assertEquals(true, $survey->roleGroups[0]->toEvaluate);
        $this->assertEquals($evaluatingRole1, $survey->roleGroups[1]->roleId);
        $this->assertEquals($evaluatingRole2, $survey->roleGroups[2]->roleId);

        $survey->delete();
    }

    /**
    * Tests creating a progress survey
    */
    public function testProgressCreate()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/survey/create');
        $this->assertEquals(200, $response->getStatusCode());

        $testPersons = [
            ['Test Testsson', 'Elo Kamil'],
            ['test@example.com', 'elo@kamil.se'],
            ['CEO', '']
        ];

        SurveyEmailer::disableMail();
        $response = $this->call('POST', '/survey/create', $this->withCSRF([
            'name' => 'Test',
            'language' => 'sv',
            'type' => 'progress',
            'startDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->toDateString(),
            'endDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->addWeek()->toDateString(),
            'description' => 'Lorem',
            'invitationSubject' => 'Invite subject',
            'invitationText' => 'Invite text',
            'reminderSubject' => 'Reminder subject',
            'reminderText' => 'Reminder text',
            'toEvaluateInvitationSubject' => 'To evaluate invitation subject',
            'toEvaluateInvitationText' => 'To evaluate invitation text',
             'candidateNames' => $testPersons[0],
            'candidateEmails' => $testPersons[1],
            'candidatePositions' => $testPersons[2]
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        SurveyEmailer::enableMail();

        //Check if created
        $survey = Survey::where('ownerId', '=', $user->id)->get()->first();
        $this->assertEquals(true, $survey != null);

        $this->assertEquals(\App\SurveyTypes::Progress, $survey->type);
        $this->assertRecipients($survey, $testPersons);
        $this->assertCandidates($survey, $testPersons);

        $survey->delete();
    }

    /**
    * Tests creating a normal survey
    */
    public function testNormalCreate()
    {
        $user = $this->signIn();
        $response = $this->call('GET', '/survey/create');
        $this->assertEquals(200, $response->getStatusCode());

        $testPersons = [
            ['Test Testsson', 'Elo Kamil'],
            ['test@example.com', 'elo@kamil.se'],
            ['CEO', '']
        ];

        SurveyEmailer::disableMail();
        $response = $this->call('POST', '/survey/create', $this->withCSRF([
            'name' => 'Test',
            'language' => 'sv',
            'type' => 'normal',
            'startDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->toDateString(),
            'endDate' => \Carbon\Carbon::now(\App\Survey::TIMEZONE)->addWeek()->toDateString(),
            'description' => 'Lorem',
            'invitationSubject' => 'Invite subject',
            'invitationText' => 'Invite text',
            'reminderSubject' => 'Reminder subject',
            'reminderText' => 'Reminder text',
            'normalParticipantNames' => $testPersons[0],
            'normalParticipantEmails' => $testPersons[1],
            'normalParticipantPositions' => $testPersons[2]
        ]));
        $this->assertEquals(200, $response->getStatusCode());
        SurveyEmailer::enableMail();

        //Check if created
        $survey = Survey::where('ownerId', '=', $user->id)->get()->first();
        $this->assertEquals(true, $survey != null);

        $this->assertEquals(\App\SurveyTypes::Normal, $survey->type);
        $this->assertRecipients($survey, $testPersons);

        $survey->delete();
    }
}