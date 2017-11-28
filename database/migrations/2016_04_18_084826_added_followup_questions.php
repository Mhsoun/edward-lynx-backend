<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedFollowupQuestions extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('question_follow_up_questions', function (Blueprint $table)
		{
			$table->increments('id');

			$table->integer('questionId')->unsigned();
			$table->foreign('questionId')
            	->references('id')
            	->on('questions')
            	->onDelete('cascade');

			$table->integer('followUpQuestionId')->unsigned();
			$table->foreign('followUpQuestionId')
				->references('id')
				->on('questions')
				->onDelete('cascade');

			$table->integer('followUpValue');
		});

		Schema::table('questions', function (Blueprint $table)
		{
			$table->boolean('isFollowUpQuestion')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('question_custom_values');
		Schema::dropIfExists('question_follow_up_questions');
		Schema::table('questions', function (Blueprint $table)
		{
			$table->dropColumn('isFollowUpQuestion')->default(false);
		});
	}
}
