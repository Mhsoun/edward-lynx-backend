<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedDefaultTexts extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('default_texts', function(Blueprint $table)
		{
			$table->increments('id');
			$table->string('lang');
			$table->text('subject')->nullable();
			$table->text('text');
			$table->integer('type')->unsigned();
			$table->integer('surveyType')->unsigned();
			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
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
		Schema::drop('default_texts');
	}
}
