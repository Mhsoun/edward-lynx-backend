<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedQuestionTags extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('question_tags', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('questionId')->unsigned();
            $table->foreign('questionId')
            	->references('id')
            	->on('questions')
            	->onDelete('cascade');

            $table->string("tag");
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('question_tags');
	}
}
