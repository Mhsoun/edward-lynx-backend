<?php

use App\Models\QuestionCategory;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class CategoryControllerTest extends TestCase
{

    public function testIndex()
    {
        $categories = factory(QuestionCategory::class, 3)->create();

        $this->apiAuthenticate()
             ->getJson('/api/v1/categories')
             ->seeJsonStructure([
                'items' => [
                    '*' => [
                        'id', 'title', 'description'
                    ]
                ]
             ]);
    }

    public function testShow()
    {
        $category = factory(QuestionCategory::class)->create();

        $this->apiAuthenticate()
             ->getJson('/api/v1/categories/' . $category->id)
             ->seeJsonStructure([
                'id',
                'title',
                'description'
            ]);
    }

}
