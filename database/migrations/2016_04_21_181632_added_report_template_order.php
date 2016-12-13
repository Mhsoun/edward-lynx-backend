<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedReportTemplateOrder extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('report_template_page_orders', function(Blueprint $table)
		{
			$table->increments('id');

			$table->integer('reportTemplateId')->unsigned();
			$table->foreign('reportTemplateId')
				->references('id')
				->on('report_templates')
				->onDelete('cascade');

			$table->integer('pageId')->unsigned();
			$table->integer('order')->unsigned();
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('report_template_page_orders');
	}
}
