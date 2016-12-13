<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedQuestionCategoryOrder extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_question_categories', function(Blueprint $table)
		{
			$table->integer('order');
		});

		Schema::table('survey_questions', function(Blueprint $table)
		{
			$table->integer('order');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('survey_question_categories', function(Blueprint $table)
		{
			$table->dropColumn('order');
		});

		Schema::table('survey_questions', function(Blueprint $table)
		{
			$table->dropColumn('order');
		});
	}
}
