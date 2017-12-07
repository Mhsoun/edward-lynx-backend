<?php
namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Survey;
use App\Models\SurveyCandidate;
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
    
}
