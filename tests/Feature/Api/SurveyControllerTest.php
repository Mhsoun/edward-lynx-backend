<?php
namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Survey;
use App\Models\Recipient;
use App\Models\SurveyCandidate;
use App\Models\SurveyRecipient;
use Tests\Helpers\SurveyHelper;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class SurveyControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function testSurveyDetailsHasDisallowedParticipants()
    {
        $helper = new SurveyHelper();
        $survey = $helper->createSurvey();
        list($candidate, $key) = $helper->createUserCandidate($survey);
        $endpoint = sprintf('/api/v1/surveys/%d?key=%s', $survey->id, $key);

        $this->actingAs($candidate, 'api');

        $this->getJson($endpoint)
             ->seeJsonStructure([
                'disallowed_recipients',
             ])
             ->seeJsonSubset([
                'disallowed_recipients' => [$candidate->email],
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
    
}
