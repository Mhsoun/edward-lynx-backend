<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeRecipientIdForeignKeyInInstantFeedbackAnswers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->dropForeign('instant_feedback_answers_user_id_foreign');
            $table->foreign('recipientId', 'instant_feedback_answers_user_id_foreign')
                  ->references('id')->on('recipients')
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
        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->foreign('recipientId', 'instant_feedback_answers_user_id_foreign')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
        });
    }
}
