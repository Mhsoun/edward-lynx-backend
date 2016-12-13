<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Recipiant extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('Recipient', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner')->unsigned();
            $table->foreign('owner')->references('id')->on('users');
            $table->string('name');//TODO replace with $table->string('name',64);
            $table->string('mail');//TODO replace with $table->string('mail',64);
        });

        Schema::create('Group', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('owner')->unsigned();
            $table->foreign('owner')->references('id')->on('users');
            $table->string('name'); //The db design document says that this field should be a string.
        });
        
        Schema::create('Recipient_In_Group', function (Blueprint $table) {
            $table->integer('member')->unsigned();
            $table->foreign('member')->references('id')->on('Recipient');
            $table->integer('group')->unsigned();
            $table->foreign('group')->references('id')->on('Group');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('Recipient_In_Group');
        Schema::drop('Group');
        Schema::drop('Recipient');

    }

}
