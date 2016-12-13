<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvalidAnswerConstraint extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_answers', function(Blueprint $table)
		{
			$table->dropForeign('survey_answers_questionid_foreign');
			$table->foreign('questionId')
            	->references('id')
            	->on('questions')
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
		Schema::table('survey_answers', function(Blueprint $table)
		{
			$table->dropForeign('survey_answers_questionid_foreign');
		});
	}
}
