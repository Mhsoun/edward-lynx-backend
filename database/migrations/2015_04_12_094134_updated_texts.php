<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatedTexts extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::rename('invitation_texts', 'email_texts');
        Schema::table('surveys', function(Blueprint $table)
        {
            $table->integer('manualRemindingTextId')
                ->unsigned()
                ->nullable();
            $table->foreign('manualRemindingTextId')
                ->references('id')
                ->on('email_texts')
                ->onDelete('cascade');

            $table->integer('toEvaluateInvitationTextId')
                ->unsigned()
                ->nullable();
            $table->foreign('toEvaluateInvitationTextId')
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
		Schema::rename('email_texts', 'invitation_texts');
        Schema::table('surveys', function(Blueprint $table)
        {
            // $table->dropColumn('manualRemindingTextId');
            // $table->dropColumn('toEvaluateInvitationTextId');
        });
	}
}
