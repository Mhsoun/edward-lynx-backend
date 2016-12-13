<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedSurveyExtraQuestions extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('survey_extra_questions', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('extraQuestionId')->unsigned();
			$table->foreign('extraQuestionId')
	        	->references('id')
	        	->on('extra_questions')
	        	->onDelete('cascade');

			$table->integer('surveyId')->unsigned();
            $table->foreign('surveyId')
            	->references('id')
            	->on('surveys')
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
		Schema::drop('survey_extra_questions');
	}
}
