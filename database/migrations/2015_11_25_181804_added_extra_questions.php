<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedExtraQuestions extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('extra_questions', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('ownerId')
				->unsigned()
				->nullable();

            $table->foreign('ownerId')
            	->references('id')
            	->on('users')
            	->onDelete('cascade');

            $table->integer('type')->unsigned();
            $table->integer('isOptional')->boolean()->default(false);
		});

		Schema::create('extra_question_names', function(Blueprint $table)
		{
			$table->string('name');
			$table->string('lang');

			$table->integer('extraQuestionId')->unsigned();
			$table->foreign('extraQuestionId')
				->references('id')
				->on('extra_questions')
				->onDelete('cascade');

			$table->primary(['name', 'lang', 'extraQuestionId']);	
		});

		Schema::create('extra_question_values', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('extraQuestionId')->unsigned();
			$table->foreign('extraQuestionId')
				->references('id')
				->on('extra_questions')
				->onDelete('cascade');
		});

		Schema::create('extra_question_value_names', function(Blueprint $table)
		{
			$table->string('name');
			$table->string('lang');

			$table->integer('valueId')->unsigned();
			$table->foreign('valueId')
				->references('id')
				->on('extra_question_values')
				->onDelete('cascade');

			$table->primary(['name', 'lang', 'valueId']);	
		});

		Schema::create('survey_extra_answers', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('extraQuestionId')->unsigned();
			$table->foreign('extraQuestionId')
				->references('id')
				->on('extra_questions')
				->onDelete('cascade');

			$table->integer('numericValue')->nullable();
			$table->text('textValue')->nullable();
			$table->date('dateValue')->nullable();

			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
            	->references('id')
            	->on('surveys')
            	->onDelete('cascade');

           $table->integer('answeredById')->unsigned();
           $table->foreign('answeredById')
            	->references('id')
            	->on('recipients')
            	->onDelete('cascade');

            $table->integer('invitedBy')->unsigned();
            $table->foreign('invitedBy')
            	->references('id')
            	->on('recipients')
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
		Schema::drop('survey_extra_answers');
		Schema::drop('extra_question_value_names');
		Schema::drop('extra_question_values');
		Schema::drop('extra_question_names');
		Schema::drop('extra_questions');
	}
}
