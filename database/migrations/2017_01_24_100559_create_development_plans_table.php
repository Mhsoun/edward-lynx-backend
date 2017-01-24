<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevelopmentPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('development_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ownerId')->unsigned();
            $table->integer('targetId')->unsigned();
            $table->string('name');
            $table->timestamps();
            
            $table->foreign('ownerId')
                ->references('id')->on('users')
                ->onDelete('cascade');
            
            $table->foreign('targetId')
                ->references('id')->on('users')
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
        Schema::dropIfExists('development_plans');
    }
}
