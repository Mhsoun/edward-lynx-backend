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

        $this->createAlphaCompany();
    }

    protected function createAlphaCompany()
    {
        $alpha = User::create([
            'id'            => 1000,
            'name'          => 'Alpha',
            'email'         => 'hello@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 1,
        ]);
        $alphaAdmin = User::create([
            'id'            => 1100,
            'name'          => 'Admin Alpha',
            'email'         => 'admin@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 1,
            'parentId'      => $alpha->id,
        ]);
        $alphaSupA = User::create([
            'id'            => 1200,
            'name'          => 'SupervisorA Alpha',
            'email'         => 'sup1@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 2,
            'parentId'      => $alpha->id,
        ]);
        $alphaSupB = User::create([
            'id'            => 1300,
            'name'          => 'SupervisorB Alpha',
            'email'         => 'sup2@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 2,
            'parentId'      => $alpha->id,
        ]);
        $alphaUserA1 = User::create([
            'id'            => 1201,
            'name'          => 'UserA1 Alpha',
            'email'         => 'ua1@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 3,
            'parentId'      => $alpha->id,
        ]);
        $alphaUserA2 = User::create([
            'id'            => 1202,
            'name'          => 'UserA2 Alpha',
            'email'         => 'ua2@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 3,
            'parentId'      => $alpha->id,
        ]);
        $alphaUserA3 = User::create([
            'id'            => 1203,
            'name'          => 'UserA3 Alpha',
            'email'         => 'ua3@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 3,
            'parentId'      => $alpha->id,
        ]);
        $alphaUserB1 = User::create([
            'id'            => 1301,
            'name'          => 'UserB1 Alpha',
            'email'         => 'ub1@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 3,
            'parentId'      => $alpha->id,
        ]);
        $alphaUserB2 = User::create([
            'id'            => 1302,
            'name'          => 'UserB2 Alpha',
            'email'         => 'ub2@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 3,
            'parentId'      => $alpha->id,
        ]);
        $alphaUserB3 = User::create([
            'id'            => 1303,
            'name'          => 'UserB3 Alpha',
            'email'         => 'ub3@alpha.com',
            'password'      => Hash::make('p123'),
            'accessLevel'   => 3,
            'parentId'      => $alpha->id,
        ]);
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
