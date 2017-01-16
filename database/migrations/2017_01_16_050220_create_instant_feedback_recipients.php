<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstantFeedbackRecipients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instant_feedback_recipients', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('instant_feedback_id');
            $table->integer('user_id');
            $table->string('key');
            $table->boolean('answered');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instant_feedback_recipients');
    }
}
