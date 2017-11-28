<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedDefaultToEvaluateMail extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
			$table->text('defaultToEvaluateEmailSubject');
			$table->text('defaultToEvaluateEmailMessage');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function(Blueprint $table)
		{
			// $table->dropColumn('defaultToEvaluateEmailSubject');
			// $table->dropColumn('defaultToEvaluateEmailMessage');
		});
	}
}
