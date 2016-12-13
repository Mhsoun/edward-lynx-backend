<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedCandidateInviteEmail extends Migration
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
			$table->integer('candidateInvitationTextId')
            	->unsigned()
            	->nullable();

	        $table->foreign('candidateInvitationTextId')
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
			$table->dropColumn('candidateInvitationTextId');
		});
	}
}
