<?php

$factory->define(App\Models\EmailText::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'lang'      => 'en',
        'subject'   => 'Sample EmailText Subject',
        'text'      => $faker->words(15, true),
        'ownerId'   => 1,
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

$factory->define(App\Models\Question::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'text'          => $faker->words(5, true),
        'ownerId'       => function() {
            return factory(App\Models\User::class)->create()->id;
        },
        'categoryId'    => function() {
            return factory(App\Models\QuestionCategory::class)->create()->id;   
        },
        'answerType'    => 0,
    ];
});

$factory->define(App\Models\QuestionCategory::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'title'         => $faker->words(3, true),
        'lang'          => 'en',
        'description'   => $faker->sentences(3, true),
        'ownerId'       => function() {
            return factory(App\Models\User::class)->create([
                'isAdmin'       => true,
                'accessLevel'   => 1,
            ])->id;
        },
        'isSurvey'      => true
    ];
});

$factory->define(App\Models\Recipient::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'ownerId'   => 1,
        'name'      => $faker->name(),
        'mail'      => $faker->safeEmail(),
        'position'  => $faker->jobTitle(),
    ];
});

$factory->define(App\Models\Survey::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'name' => $faker->words(3, true),
        'type' => 'App\SurveyTypes::Individual',
        'lang' => 'en',
        'invitationTextId' => $factory->create(App\Models\EmailText::class)->id,
        'ownerId' => 1,
        'startDate' => Carbon\Carbon::now(),
        'endDate' => Carbon\Carbon::now()->addDay(30),
        'description' => $faker->words(15, true),
        'thankYouText' => $faker->words(15, true),
        'questionInfoText' => $faker->words(15, true),
    ];
});

$factory->define(App\Models\SurveyQuestionCategory::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'surveyId'      => function() {
            return factory(App\Models\Survey::class)->create()->id;
        },
        'categoryId'    => function() {
            return factory(App\Models\QuestionCategory::class)->create()->id;
        },
        'order'         => 0,
    ];
});

$factory->define(App\Models\User::class, function (Faker\Generator $faker) use ($factory) {
    return [
        'name'          => $faker->name(),
        'email'         => $faker->safeEmail(),
        'info'          => $faker->words(5, true),
        'password'      => $faker->password(),
        'isAdmin'       => false,
        'parentId'      => 1,
        'accessLevel'   => 3,
    ];
});
