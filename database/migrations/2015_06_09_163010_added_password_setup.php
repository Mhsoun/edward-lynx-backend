<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedPasswordSetup extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('password_setups', function(Blueprint $table)
		{
			$table->string('token');
			$table->integer('userId')->unsigned();
			$table->foreign('userId')
				->references('id')
				->on('users')
				->onDelete('cascade');
			$table->dateTime('createdAt');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('password_setups');
	}
}
