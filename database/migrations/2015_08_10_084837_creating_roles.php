<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatingRoles extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('roles', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('surveyType')->unsigned();
			$table->boolean('special')->default(false);
			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
				->references('id')
				->on('users')
				->onDelete('cascade');
		});

		Schema::create('role_names', function(Blueprint $table)
		{
			$table->string('name');
			$table->string('lang');

			$table->integer('roleId')->unsigned();
			$table->foreign('roleId')
				->references('id')
				->on('roles')
				->onDelete('cascade');

			$table->primary(['name', 'lang', 'roleId']);	
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('role_names');
		Schema::drop('roles');
	}
}
