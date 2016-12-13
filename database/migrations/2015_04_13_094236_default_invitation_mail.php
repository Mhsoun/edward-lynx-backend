<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DefaultInvitationMail extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
			$table->text('defaultManualReminderEmailSubject');
			$table->text('defaultManualReminderEmailMessage');
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
			$table->dropColumn('defaultManualReminderEmailSubject');
			$table->dropColumn('defaultManualReminderEmailMessage');
		});
	}
}
