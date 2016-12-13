<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedExtraQuestionValueParent extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('extra_question_values', function(Blueprint $table)
		{
			$table->integer('parentValueId')
				->unsigned()
				->nullable();

			$table->foreign('parentValueId')
				->references('id')
				->on('extra_question_values')
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
		Schema::table('extra_question_values', function(Blueprint $table)
		{
			$table->dropColumn('parentValueId');
		});
	}
}
