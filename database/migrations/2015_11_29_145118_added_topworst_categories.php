<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedTopworstCategories extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('survey_topworst_categories', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
				->references('id')
				->on('surveys')
				->onDelete('cascade');

			$table->integer('categoryId')->unsigned();
			$table->foreign('categoryId')
				->references('id')
				->on('question_categories')
				->onDelete('cascade');

			$table->integer('answeredById')->unsigned();
			$table->foreign('answeredById')
				->references('id')
				->on('recipients')
				->onDelete('cascade');

			$table->boolean('isTop');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('survey_topworst_categories');
	}
}
