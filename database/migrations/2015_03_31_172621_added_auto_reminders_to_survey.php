<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedAutoRemindersToSurvey extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('surveys', function(Blueprint $table)
        {
            $table->boolean("enableAutoReminding")->default(false);
            $table->dateTime("autoRemindingDate")->nullable();
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
            $table->dropColumn('enableAutoReminding');
            $table->dropColumn('autoRemindingDate');
        });
	}
}
