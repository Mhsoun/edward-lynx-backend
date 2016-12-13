<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedTeamInviteEmail extends Migration
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
			$table->integer('evaluatedTeamInvitationTextId')
            	->unsigned()
            	->nullable();

	        $table->foreign('evaluatedTeamInvitationTextId')
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
			$table->dropColumn('evaluatedTeamInvitationTextId');
		});
	}
}
