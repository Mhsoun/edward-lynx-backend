<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class InvalidGroupConstraints extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Group', function (Blueprint $table) {
			$table->dropForeign('Group_owner_foreign');
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
		//No point of reversing.
	}
}
