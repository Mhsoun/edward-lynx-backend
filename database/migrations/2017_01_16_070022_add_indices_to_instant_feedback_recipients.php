<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddIndicesToInstantFeedbackRecipients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->integer('instant_feedback_id')->unsigned()->change();
            $table->foreign('instant_feedback_id')
                  ->references('id')->on('instant_feedbacks')
                  ->onDelete('cascade');
            
            $table->integer('user_id')->unsigned()->change();
            $table->foreign('user_id')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
            
            $table->index('key');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->dropForeign(['instant_feedback_id']);
            $table->dropForeign(['user_id']);
            $table->dropindex(['key']);
        });
    }
}
