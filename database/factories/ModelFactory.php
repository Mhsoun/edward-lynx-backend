<?php

$factory->define(App\Models\QuestionCategory::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'title'         => $faker->words(3, true),
        'lang'          => 'en',
        'description'   => $faker->sentences(3, true),
        'ownerId'       => 1,
        'isSurvey'      => true
    ];
});

$factory->define(App\Models\DevelopmentPlan::class, function (Faker\Generator $faker) {
    return [
        'ownerId'       => 1,
        'name'          => $faker->words(3, true),
        'checked'       => $faker->boolean
    ];
});

$factory->define(App\Models\DevelopmentPlanGoal::class, function (Faker\Generator $faker) use ($factory) {
    $devPlan = $factory->create(App\Models\DevelopmentPlan::class);
    return [
        'developmentPlanId' => $devPlan->id,
        'title'             => $faker->words(5, true),
        'description'       => $faker->sentences(16, true),
        'checked'           => $devPlan->checked ? true : $faker->boolean
    ];
});

$factory->define(App\Models\DevelopmentPlanGoalAction::class, function (Faker\Generator $faker) use ($factory) {
    $goal = $factory->create(App\Models\DevelopmentPlanGoal::class);
    return [
        'goalId'    => $goal->id,
        'title'     => $faker->words(3, true),
        'checked'   => $goal->checked ? true : $faker->boolean
    ];
});