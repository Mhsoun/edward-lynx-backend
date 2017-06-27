<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateManagedUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('managed_users', function (Blueprint $table) {
            $table->unsignedInteger('managerId');
            $table->unsignedInteger('userId');
            $table->primary(['managerId', 'userId']);

            $table->foreign('managerId')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            $table->foreign('userId')
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
        Schema::dropIfExists('managed_users');
    }
}
