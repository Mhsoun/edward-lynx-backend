<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SurveyThankYou extends Migration
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
			$table->text('thankYouText');
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
			$table->dropColumn('thankYouText');
		});
	}
}
