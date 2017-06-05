<?php

use Carbon\Carbon;
use App\Models\DevelopmentPlan;
use App\Models\QuestionCategory;
use App\Models\DevelopmentPlanGoalAction;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DevelopmentPlanControllerTest extends TestCase
{
    use AssertsCreatedResource;

    public function testIndex()
    {
        $devPlans = factory(DevelopmentPlanGoalAction::class, 3)->create();

        $this->api('GET', '/api/v1/dev-plans')
             ->seeJsonStructure([
                'items' => ['*' => [
                    'id',
                    'name',
                    'createdAt',
                    'updatedAt',
                    'checked',
                    'goals' => ['*' => [
                            'id',
                            'title',
                            'description',
                            'checked',
                            'position',
                            'dueDate',
                            'reminderSent',
                            'categoryId',
                            'actions'   => ['*' => [
                                'id',
                                'title',
                                'checked',
                                'position'
                            ]]
                        ]
                    ]]
                ]
             ]);
    }

    public function testCreate()
    {
        $category = factory(QuestionCategory::class)->create();

        $this->authenticateApi()
             ->postJson('/api/v1/dev-plans', [
                'name'          => 'test dev plan',
                'categoryId'    => $category->id,
                'goals'         => [
                    [
                        'title'         => 'test goal 1',
                        'description'   => 'test goal 1 description',
                        'dueDate'       => Carbon::now()->toIso8601String(),
                        'position'      => 0,
                        'actions'       => [
                            [
                                'title'     => 'test goal 1 action 1',
                                'position'  => 0
                            ],
                            [
                                'title'     => 'test goal 1 action 2',
                                'position'  => 1
                            ],
                            [
                                'title'     => 'test goal 1 action 3',
                                'position'  => 2
                            ]
                        ]
                    ],
                    [
                        'title'         => 'test goal 2',
                        'description'   => 'test goal 2 description',
                        'dueDate'       => Carbon::now()->toIso8601String(),
                        'position'      => 1,
                        'actions'       => [
                            [
                                'title'     => 'test goal 2 action 1',
                                'position'  => 0
                            ],
                            [
                                'title'     => 'test goal 2 action 2',
                                'position'  => 1
                            ],
                            [
                                'title'     => 'test goal 2 action 3',
                                'position'  => 2
                            ]
                        ]
                    ]
                ]
            ]);

        $this->assertCreatedResponse();
        $this->assertCreatedResource('development_plans');
    }

    public function testShow()
    {

    }

    

}
