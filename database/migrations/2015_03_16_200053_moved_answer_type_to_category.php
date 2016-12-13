<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MovedAnswerTypeToCategory extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('questions', function(Blueprint $table)
		{
			$table->dropColumn('answerType');
		});

		Schema::table('question_categories', function(Blueprint $table)
		{
			$table->integer('answerType')->unsigned();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('questions', function(Blueprint $table)
		{
			$table->string('answerType');
		});

		Schema::table('question_categories', function(Blueprint $table)
		{
			$table->dropColumn('answerType');
		});
	}
}
