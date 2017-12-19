<?php
namespace Tests\Feature\Api;

use Faker\Factory;
use Tests\TestCase;
use App\Models\User;
use App\Models\Survey;
use App\Models\Question;
use App\Models\Recipient;
use App\Models\SurveyQuestion;
use App\Models\SurveyCandidate;
use App\Models\SurveyRecipient;
use Tests\Helpers\SurveyHelper;
use App\Models\QuestionCategory;
use App\Models\SurveyQuestionCategory;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class SurveyControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testSurveysMineEndpoint()
    {
        $faker = \Faker\Factory::create();
        $helper = new SurveyHelper();
        $company = factory(User::class)->create([
            'name'          => $faker->company,
            'isAdmin'       => true,
            'accessLevel'   => 1,
        ]);
        $admin = factory(User::class)->create([
            'parentId'      => $company->id,
            'isAdmin'       => true,
            'accessLevel'   => 1,
        ]);

        $survey1 = $helper->createBlankSurvey([
            'ownerId'       => $admin->id,
        ]);
        $survey2 = $helper->createBlankSurvey([
            'ownerId'       => $admin->id,
        ]);

        $this->actingAs($admin, 'api');

        $this->getJson('/api/v1/surveys/?filter=mine')
             ->seeJsonSubset([
                'items' => [
                    [ 'id' => $survey1->id ],
                    [ 'id' => $survey2->id ],
                ]
             ]);
    }

    public function testSurveyDetailsHasDisallowedParticipants()
    {
        $helper = new SurveyHelper();
        $survey = $helper->createSurvey();
        list($candidate, $key) = $helper->createUserCandidate($survey);
        
        // Fetch one participant for the current candidate.
        $recipientIds = Recipient::recipientIdsOfUser($candidate);
        $surveyCandidate = $survey->candidates()->whereIn('recipientId', $recipientIds)->first();
        $invitedParticipant = $survey->recipients()->where('invitedById', $surveyCandidate->recipient->id)->skip(1)->first()->recipient; // First record is usually the candidate invite.

        // Fetch another participant for the other candidate
        $surveyCandidate2 = $survey->candidates->last();
        $invitedParticipant2 = $survey->recipients()->where('invitedById', $surveyCandidate2->recipient->id)->skip(1)->first()->recipient;  // First record is usually the candidate invite.

        $endpoint = sprintf('/api/v1/surveys/%d?key=%s', $survey->id, $key);

        $this->actingAs($candidate, 'api');

        $this->getJson($endpoint)
             ->seeJsonSubset([
                'disallowed_recipients' => [$candidate->email, $invitedParticipant->mail],
             ])
             ->dontSeeJson([
                'disallowed_recipients' => [$invitedParticipant2->mail],
             ]);
    }

    public function testCorrectStatusForListAndShowSurveys()
    {
        $helper = new SurveyHelper();
        $survey = $helper->createBlankSurvey();

        $candidate1 = factory(Recipient::class)->create(['ownerId' => $survey->ownerId]);
        $candidate2 = factory(Recipient::class)->create(['ownerId' => $survey->ownerId]);
        $participant = factory(Recipient::class)->create(['ownerId' => $survey->ownerId]);

        $user = factory(User::class)->make([
            'name'      => $participant->name,
            'email'     => $participant->mail,
            'parentId'  => $survey->owner->company->id,
        ]);

        $candidate1Sr = $survey->addRecipient($candidate1->id, 1, $survey->ownerId);
        $survey->addCandidate($candidate1, $candidate1Sr->link);
        $survey->addRecipient($participant->id, 1, $candidate1->id);

        $candidate2Sr = $survey->addRecipient($candidate2->id, 1, $survey->ownerId);
        $survey->addCandidate($candidate2, $candidate2Sr->link);
        $survey->addRecipient($participant->id, 1, $candidate2->id);

        $this->actingAs($user, 'api');

        $answerables = $this->getJson('/api/v1/surveys?filter=answerable')->decodeResponseJson();
        $key1 = $answerables['items'][0]['key'];
        $key2 = $answerables['items'][1]['key'];

        SurveyRecipient::where('link', $key1)->first()->fill(['hasAnswered' => 1])->save();

        $this->getJson(sprintf('/api/v1/surveys/%d?key=%s', $survey->id, $key2))
             ->seeJsonSubset([
                'status' => SurveyRecipient::NO_ANSWERS,
             ]);
    }

    public function testCanInviteFieldIsTrueForCandidates()
    {
        $helper = new SurveyHelper();
        $survey = $helper->createSurvey();
        list($candidate, $key) = $helper->createUserCandidate($survey);

        $this->actingAs($candidate, 'api');

        $endpoint = sprintf('/api/v1/surveys?filter=answerable', $survey->id, $key);
        $this->getJson($endpoint)
             ->seeJsonSubset([
                'items' => [
                    [
                        'permissions' => [
                            'can_invite' => true,
                        ],
                    ]
                ]
             ]);
    }

    public function testQuestionsEndpoint()
    {
        $faker = \Faker\Factory::create();
        $helper = new SurveyHelper();
        $survey = $helper->createSurvey();
        $candidate = $helper->createUserCandidate($survey)[0];

        $category = factory(QuestionCategory::class)->create([
            'ownerId' => $survey->ownerId,
        ]);
        $surveyQuestionCategory = factory(SurveyQuestionCategory::class)->create([
            'surveyId'      => $survey->id,
            'categoryId'    => $category->id,
        ]);
        for ($i = 0; $i < 3; $i++) {
            $question = factory(Question::class)->create([
                'ownerId'       => $survey->ownerId,
                'categoryId'    => $category->id,
            ]);
            factory(SurveyQuestion::class)->create([
                'surveyId'      => $survey->id,
                'questionId'    => $question->id,
                'order'         => $i,
            ]);
        }

        $this->actingAs($candidate, 'api');

        $endpoint = sprintf('/api/v1/surveys/%d/questions', $survey->id);
        $this->getJson($endpoint);
        $this->seeJsonStructure([
            [
                'questions' => [],
            ],
        ]);
    }

    public function testExchangeEndpoint()
    {
        $helper = new SurveyHelper();
        $survey = $helper->createSurvey();
        list($candidate, $key) = $helper->createUserCandidate($survey);

        $this->actingAs($candidate, 'api');

        $endpoint = sprintf('/api/v1/surveys/exchange/%s', $key);
        $this->getJson($endpoint);
        $this->seeJsonSubset([
            'survey_id' => $survey->id,
        ]);
    }
    
}
