<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Recipiantsinforms extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('RecipientsInForm', function (Blueprint $table) {
            $table->integer('form')->unsigned();
            $table->foreign('form')->references('id')->on('forms');
            $table->integer('recp')->unsigned();
            $table->foreign('recp')->references('id')->on('Recipient');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('RecipientsInForm');
    }

}
