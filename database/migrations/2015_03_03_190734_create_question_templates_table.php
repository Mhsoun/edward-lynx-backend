<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateQuestionTemplatesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('question_templates', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('text');
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
		Schema::drop('question_templates');
	}
}
