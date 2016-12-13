<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedUserReports extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('survey_user_reports', function(Blueprint $table)
        {
			$table->string('link');
			$table->primary('link');

            $table->integer('surveyId')->unsigned();
            $table->foreign('surveyId')
                ->references('id')
                ->on('surveys')
                ->onDelete('cascade');

            $table->integer('recipientId')->unsigned();
            $table->foreign('recipientId')
                ->references('id')
                ->on('recipients')
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
		Schema::drop('survey_user_reports');
	}
}
