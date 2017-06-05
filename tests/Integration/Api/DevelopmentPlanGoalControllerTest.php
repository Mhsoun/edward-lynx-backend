<?php

use Carbon\Carbon;
use App\Models\DevelopmentPlan;
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

}
