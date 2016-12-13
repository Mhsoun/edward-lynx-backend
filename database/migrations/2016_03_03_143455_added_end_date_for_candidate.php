<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedEndDateForCandidate extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_invite_recipients', function(Blueprint $table)
		{
			$table->dateTime('endDate')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('survey_invite_recipients', function (Blueprint $table)
		{
			$table->dropColumn('endDate');
		});
	}
}
