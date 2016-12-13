<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Invitelang extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('forms', function ($table) {
             $table->dropColumn('lang');
             $table->dropColumn('invite_text');
           // $table->integer('');
        });

        Schema::create('invite_text', function (Blueprint $table) {
            $table->string('lang');
            $table->string('text',64);
            $table->integer('owner')->unsigned();
            $table->foreign('owner')->references('id')->on('forms');
        });


        }

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('forms', function ($table) {
            $table->string('lang');
            $table->string('invite_text');
            // $table->integer('');
        });

        Schema::drop('invite_text');
	}

}
