<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddInviteOthersRemindersEmail extends Migration
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
			$table->integer('inviteOthersReminderTextId')
            	->unsigned()
            	->nullable();

	        $table->foreign('inviteOthersReminderTextId')
	            ->references('id')
	            ->on('email_texts')
	            ->onDelete('cascade');
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
			$table->dropForeign('surveys_inviteothersremindertextid_foreign');
			$table->dropColumn('inviteOthersReminderTextId');
		});
	}
}
