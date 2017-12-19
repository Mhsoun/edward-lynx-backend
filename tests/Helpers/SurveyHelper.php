<?php
namespace Tests\Helpers;

use App\Models\User;
use App\Models\Survey;
use App\Models\Recipient;

class SurveyHelper
{

    /**
     * Creates a sample 360 survey.
     *
     * @return App\Models\Survey
     */
    public function createSurvey()
    {
        $faker = \Faker\Factory::create();
        $survey = $this->createBlankSurvey();
        
        // Create candidates
        for ($i = 0; $i < 3; $i++) {
            $candidate = factory(Recipient::class)->create([
                'ownerId'   => $survey->ownerId,
            ]);
            $surveyRecipient = $survey->addRecipient($candidate->id, 1, $candidate->id);
            $survey->addCandidate($candidate, $surveyRecipient->link);
            
            // Create participants for the candidate
            for ($j = 0; $j < 3; $j++) {
                $recipient = factory(Recipient::class)->create();
                $survey->addRecipient($recipient->id, 1, $candidate->id);
            }
        }

        return $survey;
    }

    /**
     * Creates a blank 360 survey without any candidates and participans.
     *
     * @param array $attributes
     * @return App\Models\Survey
     */
    public function createBlankSurvey(array $attributes = [])
    {
        $faker = \Faker\Factory::create();

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

        $newAttributes = array_merge([
            'ownerId'       => $admin->id,
        ], $attributes);

        $survey = factory(Survey::class)->create($newAttributes);

        return $survey;
    }

    /**
     * Creates a user account for the first survey candidate.
     * 
     * @param App\Models\Survey $survey
     * @return App\Models\User
     */
    public function createUserCandidate(Survey $survey)
    {
        $candidate = $survey->candidates()->first();
        $user = factory(User::class)->make([
            'name'      => $candidate->recipient->name,
            'email'     => $candidate->recipient->mail,
            'parentId'  => $survey->owner->company->id,
        ]);

        return [$user, $candidate->link];
    }

}
