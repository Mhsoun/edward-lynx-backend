<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedDescriptionToCategory extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('question_category_templates', function(Blueprint $table)
		{
			$table->text('description');
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
		Schema::table('question_category_templates', function(Blueprint $table)
		{
			$table->dropColumn('description');
		});
		*/
	}
}
