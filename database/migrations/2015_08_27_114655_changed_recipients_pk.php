<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangedRecipientsPk extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_recipients', function(Blueprint $table)
		{
			$table->dropColumn('invitedBy');
		});

		Schema::table('survey_recipients', function(Blueprint $table)
		{
			$table->integer('invitedBy')->unsigned();
		});

		Schema::table('survey_recipients', function(Blueprint $table)
		{
			DB::unprepared('ALTER TABLE `survey_recipients` DROP PRIMARY KEY, ADD PRIMARY KEY (`surveyId`, `recipientId`, `invitedBy`)');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('survey_recipients', function(Blueprint $table)
		{
			DB::unprepared('ALTER TABLE `survey_recipients` DROP PRIMARY KEY, ADD PRIMARY KEY (`surveyId`, `recipientId`)');
		});
	}
}
