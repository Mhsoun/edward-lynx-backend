<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedCopyToCategory extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('question_categories', function (Blueprint $table)
		{
			$table->boolean('isSurvey')->default(false);
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('question_categories', function (Blueprint $table)
		{
			$table->dropColumn('isSurvey');
		});
	}
}
