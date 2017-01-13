<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateInstantFeedbackQuestions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('instant_feedback_questions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('instant_feedback_id')->unsigned();
            $table->integer('question_id')->unsigned();
            
            $table->foreign('instant_feedback_id')
                  ->references('id')->on('instant_feedbacks')
                  ->onDelete('cascade');
            
            $table->foreign('question_id')
                  ->references('id')->on('questions');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('instant_feedback_questions');
    }
}
