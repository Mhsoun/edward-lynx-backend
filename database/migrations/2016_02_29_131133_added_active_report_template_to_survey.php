<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedActiveReportTemplateToSurvey extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('surveys', function(Blueprint $table)
		{
			$table->integer('activeReportTemplateId')->unsigned()->nullable();
			$table->foreign('activeReportTemplateId')
				->references('id')
				->on('report_templates')
				->onDelete('set null');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('surveys', function(Blueprint $table)
		{
			$table->dropForeign('surveys_activereporttemplateid_foreign');
			$table->dropColumn('activeReportTemplateId');
		});
	}
}
