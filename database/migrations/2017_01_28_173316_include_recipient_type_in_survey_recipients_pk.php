<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class IncludeRecipientTypeInSurveyRecipientsPk extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('survey_recipients', function (Blueprint $table) {
            DB::unprepared('ALTER TABLE `survey_recipients` DROP PRIMARY KEY, ADD PRIMARY KEY (`surveyId`, `recipientId`, `invitedById`, `recipientType`)');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('survey_recipients', function (Blueprint $table) {
            DB::unprepared('ALTER TABLE `survey_recipients` DROP PRIMARY KEY, ADD PRIMARY KEY (`surveyId`, `recipientId`, `invitedById`)');
        });
    }
}
