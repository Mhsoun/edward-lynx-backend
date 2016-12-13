<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Forms extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::create('forms', function(Blueprint $table)
        {
            $table->increments('id');
            $table->integer('owner')->unsigned();
            $table->foreign('owner')->references('id')->on('users');
            $table->string('name');//TODO replace with $table->string('name',64);
            //§table->string('target');
            $table->boolean('group');
            $table->string('lang');
        });
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::drop('forms');
	}
}
