<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Question;
use App\Models\QuestionCategory;

/**
* Seeds data for unit tests
*/
class TestDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();
        $this->call('DatabaseSeeder');
        $this->call('TestUserTableSeeder');
        $this->call('TestQuestionTableSeeder');
    }
}

class TestUserTableSeeder extends Seeder
{
    public function run()
    {
        //Delete previous values
        User::where('name', '!=', 'Edward Lynx')->delete();

        $superAdmin = User::create([
            'name' => 'SuperAdmin',
            'email' => 'admin@super.com',
            'info' => '',
            'password' => Hash::make("password123")
        ]);
        $superAdmin->isAdmin = true;
        $superAdmin->isValidated = true;
        $superAdmin->save();

        $newUser = User::create([
            'name' => 'Pizza AB',
            'email' => 'admin@pizza.se',
            'info' => 'Testing',
            'password' => Hash::make("password123")
        ]);
        $newUser->isValidated = true;
        $newUser->allowedSurveyTypes = \App\SurveyTypes::createInt([
            \App\SurveyTypes::Individual,
            \App\SurveyTypes::Group,
            \App\SurveyTypes::Progress,
            \App\SurveyTypes::LTT,
            \App\SurveyTypes::Normal
        ]);

        $newUser->save();

        $newUser2 = User::create([
            'name' => 'Troll AB',
            'email' => 'admin@troll.se',
            'info' => 'Testing',
            'password' => Hash::make("password123")
        ]);

        $newUser2->isValidated = false;
        $newUser2->save();
    }
}

class TestQuestionTableSeeder extends Seeder
{
    public function run()
    {
        //Delete previous values
        QuestionCategory::where('id', 'like', '%%')->delete();
        Question::where('id', 'like', '%%')->delete();

        $user = User::where('name', '=', 'Pizza AB')->first();

        foreach (\App\SurveyTypes::all() as $surveyType) {
            for ($i = 1; $i <= 5; $i++) {
                $category = new QuestionCategory;
                $category->ownerId = $user->id;
                $category->title = 'C' . $i;
                $category->lang = 'en';
                $category->targetSurveyType = $surveyType;
                $category->save();

                for ($j = 1; $j <= 10; $j++) {
                    $question = new Question;
                    $question->text = "Q" . $j;
                    $question->ownerId = $user->id;
                    $question->answerType = \App\AnswerType::answerTypes()[0]->id();
                    $category->questions()->save($question);
                }
            }
        }
    }
}
