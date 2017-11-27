<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class QTitle extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('questionTitles', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('owner')->unsigned();
            $table->foreign('owner')->references('id')->on('forms');
            $table->string('qTitle');
            $table->integer('answerOptions');
        });	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::dropIfExists('questionTitles');

    }

}
