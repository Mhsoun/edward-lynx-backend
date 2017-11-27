<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Answer2 extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
    {
        Schema::table('answers', function ($table) {
            $table->integer('answerdBy')->unsigned();
            $table->foreign('answerdBy')->references('id')->on('Recipient');
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
        Schema::table('answers', function ($table) {
            $table->dropForeign('answerdBy');
		});
		*/
	}

}
