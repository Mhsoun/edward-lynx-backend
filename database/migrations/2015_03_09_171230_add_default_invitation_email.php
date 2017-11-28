<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDefaultInvitationEmail extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function(Blueprint $table)
		{
			$table->text("defaultInvitationEmailSubject");
			$table->text("defaultInvitationEmailMessage");
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
			// $table->dropColumn('defaultInvitationEmailSubject');
			// $table->dropColumn('defaultInvitationEmailMessage');
		});
	}

}
