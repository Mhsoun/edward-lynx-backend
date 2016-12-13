<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedSurveyQuestionInfoText extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('surveys', function(Blueprint $table)
		{
			$table->text('questionInfoText');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('surveys', function(Blueprint $table)
		{
			$table->dropColumn('questionInfoText');
		});
	}
}
