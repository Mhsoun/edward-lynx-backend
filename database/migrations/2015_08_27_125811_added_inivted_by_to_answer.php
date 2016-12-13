<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedInivtedByToAnswer extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_answers', function(Blueprint $table)
		{
			$table->integer('invitedBy')->unsigned();
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
			$table->dropColumn('invitedBy');
		});
	}
}
