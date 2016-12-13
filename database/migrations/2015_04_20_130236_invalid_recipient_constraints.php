<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvalidRecipientConstraints extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Recipient', function (Blueprint $table) {
            $table->dropForeign('Recipient_owner_foreign');
            $table->foreign('owner')
            	->references('id')
            	->on('users')
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
		//No point of recreating
	}
}
