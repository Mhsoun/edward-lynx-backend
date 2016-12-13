<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RecipientInGroupVeryInvalidForeignKey extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Recipient_In_Group', function(Blueprint $table)
		{
			$table->dropForeign('recipient_in_group_group_foreign');
			$table->foreign('group')
				->references('id')
				->on('Group')
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
		//Don't bother recrate.
	}
}
