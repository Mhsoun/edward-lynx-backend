<?php

use Carbon\Carbon;
use App\Models\DevelopmentPlan;
use App\Models\QuestionCategory;
use App\Models\DevelopmentPlanGoal;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DevelopmentPlanGoalControllerTest extends TestCase
{
    use AssertsCreatedResource;

    public function testCreate()
    {
        $devPlan = factory(DevelopmentPlan::class)->create();

        $this->apiAuthenticate()
             ->postJson('/api/v1/dev-plans/'. $devPlan->id .'/goals', [
                'title'         => 'test dev plan goal',
                'description'   => 'test dev plan goal description',
                'dueDate'       => Carbon::now()->toIso8601String(),
                'position'      => 0,
                'actions'       => [
                    [
                        'title'     => 'goal action 1',
                        'position'  => 0,
                    ],
                    [
                        'title'     => 'goal action 2',
                        'position'  => 1
                    ]
                ]
            ]);

        $this->assertCreatedResource('development_plan_goals');
    }

    public function testUpdate()
    {
        $goal = factory(DevelopmentPlanGoal::class)->create([
            'categoryId'    => factory(QuestionCategory::class)->create()->id,
            'dueDate'       => Carbon::now()
        ]);

        $this->apiAuthenticate()
             ->patchJson('/api/v1/dev-plans/'. $goal->developmentPlan->id .'/goals/'. $goal->id, [
                'title'         => 'new goal title',
                'description'   => 'new goal description',
                'dueDate'       => null,
                'categoryId'    => null
             ]);

        $goal = DevelopmentPlanGoal::find($goal->id);

        $this->assertResponseOk();
        $this->assertEquals('new goal title', $goal->title);
        $this->assertEquals('new goal description', $goal->description);
        $this->assertNull($goal->dueDate);
        $this->assertNull($goal->cataegoryId);
    }

}
