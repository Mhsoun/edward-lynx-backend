<?php

use App\Models\DevelopmentPlanGoal;
use App\Models\DevelopmentPlanGoalAction;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DevelopmentPlanGoalActionControllerTest extends TestCase
{
    use AssertsCreatedResource, AssertsDeletedResource;

    public function testCreate()
    {
        $goal = factory(DevelopmentPlanGoal::class)->create();

        $this->apiAuthenticate()
             ->postJson('/api/v1/dev-plans/'. $goal->developmentPlanId .'/goals/'. $goal->id .'/actions', [
                'title'     => 'test dev plan goal action 1',
                'position'  => 1
            ]);

        $this->assertCreatedResource('development_plan_goal_actions');
    }

    public function testDelete()
    {
        $action = factory(DevelopmentPlanGoalAction::class)->create();

        $this->apiAuthenticate()
             ->deleteJson('/api/v1/dev-plans/'. $action->goal->developmentPlan->id . '/goals/' . $action->goal->id . '/actions/' . $action->id);

        $this->assertDeletedResource();
    }

}
