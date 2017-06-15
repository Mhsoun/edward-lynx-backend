<?php

use App\Models\DevelopmentPlanGoal;
use App\Models\DevelopmentPlanGoalAction;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DevelopmentPlanGoalTest extends TestCase
{

    public function testUpdateChecked()
    {
        // $checkedAction = factory(DevelopmentPlanGoalAction::class)->create([
            // 'checked' => true
        // ]);
        // $checkedGoal = $checkedAction->goal->first();
        // $this->assertEquals(true, $checkedGoal->checked);

        $uncheckedAction = factory(DevelopmentPlanGoalAction::class)->create([
            'checked' => false
        ]);
        $uncheckedGoal = $uncheckedAction->goal->first();
        $this->assertEquals(false, $uncheckedGoal->checked);
    }

}
