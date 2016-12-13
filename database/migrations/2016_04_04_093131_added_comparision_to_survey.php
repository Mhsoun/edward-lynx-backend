<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedComparisionToSurvey extends Migration
{

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('surveys', function (Blueprint $table)
		{
			$table->integer('compareAgainstSurveyId')
				->unsigned()
				->nullable();

			$table->foreign('compareAgainstSurveyId')
				->references('id')
				->on('surveys')
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
		Schema::table('surveys', function (Blueprint $table)
		{
			$table->dropForeign('surveys_compareagainstsurveyid_foreign');
			$table->dropColumn('compareAgainstSurveyId');
		});
	}
}
