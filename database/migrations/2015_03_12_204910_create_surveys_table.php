<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveysTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		//Drop previous tables
		Schema::drop('answers');
		Schema::drop('invite_text');
		Schema::drop('questions');
		Schema::drop('questionTitles');
		Schema::drop('RecipientsInForm');
		Schema::drop('forms');

		//Create new
		Schema::create('invitation_texts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('lang');
			$table->text('subject');
			$table->text('text');
			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
            	->references('id')
            	->on('users')
            	->onDelete('cascade');
		});

		Schema::create('surveys', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');
			$table->integer('type')->unsigned();
			$table->string('lang');

			$table->integer('invitationTextId')->unsigned();
			$table->foreign('invitationTextId')
            	->references('id')
            	->on('invitation_texts')
            	->onDelete('cascade');

			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
            	->references('id')
            	->on('users')
            	->onDelete('cascade');

            $table->dateTime('startDate');
            $table->dateTime('endDate');

            $table->boolean('creationCompleted')->default(false);

            $table->integer('groupId')->unsigned()->nullable();
            $table->foreign('groupId')
            	->references('id')
            	->on('Group')
            	->onDelete('set null');
		});

		Schema::create('question_categories', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('title');
			$table->string('lang');
			$table->text('description');

			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
            	->references('id')
            	->on('users')
            	->onDelete('cascade');
		});

		Schema::create('questions', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('text');
			$table->string('answerType');

			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
            	->references('id')
            	->on('users')
            	->onDelete('cascade');

            $table->integer('categoryId')->unsigned();
			$table->foreign('categoryId')
            	->references('id')
            	->on('question_categories')
            	->onDelete('cascade');
		});

		Schema::create('survey_question_categories', function(Blueprint $table)
		{
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
            	->references('id')
            	->on('surveys')
            	->onDelete('cascade');

			$table->integer('categoryId')->unsigned();
			$table->foreign('categoryId')
            	->references('id')
            	->on('question_categories')
            	->onDelete('cascade');

            $table->primary(['surveyId', 'categoryId']);
		});

		Schema::create('survey_questions', function(Blueprint $table)
		{
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
            	->references('id')
            	->on('surveys')
            	->onDelete('cascade');

			$table->integer('questionId')->unsigned();
			$table->foreign('questionId')
            	->references('id')
            	->on('questions')
            	->onDelete('cascade');

            $table->primary(['surveyId', 'questionId']);
		});

		Schema::create('survey_recipients', function(Blueprint $table)
		{
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
            	->references('id')
            	->on('surveys')
            	->onDelete('cascade');

			$table->integer('recipientId')->unsigned();
			$table->foreign('recipientId')
            	->references('id')
            	->on('Recipient')
            	->onDelete('cascade');

            $table->primary(['surveyId', 'recipientId']);
		});

		Schema::create('survey_answers', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('answerValue')->nullable();
			$table->text('answerText')->nullable();
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
            	->references('id')
            	->on('surveys')
            	->onDelete('cascade');

           $table->integer('answeredById')->unsigned();
           $table->foreign('answeredById')
            	->references('id')
            	->on('Recipient')
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
		Schema::drop('survey_question_categories');
		Schema::drop('survey_questions');
		Schema::drop('survey_recipients');
		Schema::drop('survey_answers');
		Schema::drop('surveys');
		Schema::drop('invitation_texts');
		Schema::drop('questions');
		Schema::drop('question_categories');
	}
}
