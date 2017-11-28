<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedLangAndCategoryIdToTempalte extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('question_templates', function(Blueprint $table)
		{
			$table->integer('answerMinValue');
			$table->integer('answerMaxValue');
			$table->string('lang');
			$table->integer('categoryId')->unsigned();
			$table->foreign('categoryId')
				->references('id')
				->on('question_category_templates')
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
		/*
		Schema::table('question_templates', function($table)
		{
			$table->dropColumn('answerMinValue');
			$table->dropColumn('answerMaxValue');
			$table->dropColumn('lang');
			$table->dropColumn('categoryId');
		});
		*/
	}
}
