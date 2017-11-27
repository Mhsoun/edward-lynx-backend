<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedCustomValues extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('question_custom_values', function (Blueprint $table)
		{
			$table->increments('id');
			$table->string('name');

			$table->integer('questionId')->unsigned();
			$table->foreign('questionId')
            	->references('id')
            	->on('questions')
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
		// Schema::drop('question_custom_values');
	}
}
