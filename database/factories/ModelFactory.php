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