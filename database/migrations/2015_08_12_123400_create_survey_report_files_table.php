<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveyReportFilesTable extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('survey_report_files', function(Blueprint $table)
		{
			$table->increments('id');
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
				->references('id')
				->on('surveys')
				->onDelete('cascade');
			$table->string('fileName');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('survey_report_files');
	}
}
