<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedReportTemplate extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('report_templates', function(Blueprint $table)
		{
			$table->increments('id');
			$table->text('name');
			$table->integer('surveyType')->unsigned();
			$table->string('lang');

			$table->integer('ownerId')->unsigned();
			$table->foreign('ownerId')
				->references('id')
				->on('users')
				->onDelete('cascade');
		});

		Schema::create('report_template_diagrams', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('reportTemplateId')->unsigned();
			$table->foreign('reportTemplateId')
				->references('id')
				->on('report_templates')
				->onDelete('cascade');

			$table->integer('typeId')->unsigned();

			$table->text('title')->nullable();
			$table->text('text');
			$table->boolean('isDiagram');
			$table->boolean('includeDiagram')->nullable();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('report_template_diagrams');
		Schema::drop('report_templates');
	}
}
