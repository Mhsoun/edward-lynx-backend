<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedAddedbyToRecipient extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_recipients', function(Blueprint $table)
		{
			$table->integer('invitedBy')
				->unsigned()
				->nullable();
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
			$table->dropColumn('invitedBy');
		});
	}
}
