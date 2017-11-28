<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Questions extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('questions', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('owner')->unsigned();
            $table->foreign('owner')->references('id')->on('questionTitles');
            $table->string('qText');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::dropIfExists('questions');

    }

}
