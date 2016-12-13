<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SelectRecipiants extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('survey_invite_recipients', function(Blueprint $table)
        {
            $table->integer('surveyId')->unsigned();
            $table->foreign('surveyId')
                ->references('id')
                ->on('surveys')
                ->onDelete('cascade');

            $table->integer('recipientId')->unsigned();
            $table->foreign('recipientId')
                ->references('id')
                ->on('Recipient')
                ->onDelete('cascade');

            $table->primary(['surveyId', 'recipientId']);
            $table->string('link')->unique();

        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('survey_invite_recipients');
	}

}
