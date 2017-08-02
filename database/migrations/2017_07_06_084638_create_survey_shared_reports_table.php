<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveySharedReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_shared_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('surveyId')->unsigned();
            $table->integer('recipientId')->unsigned()->nullable();
            $table->integer('userId')->unsigned();

            $table->foreign('surveyId')
                  ->references('id')->on('surveys')
                  ->onDelete('cascade');
            $table->foreign('recipientId')
                  ->references('id')->on('recipients')
                  ->onDelete('cascade');
            $table->foreign('userId')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('survey_shared_reports');
    }
}
