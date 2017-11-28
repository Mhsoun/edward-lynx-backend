<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameUserIdToRecipientIdInInstantFeedbackAnswers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->renameColumn('userId', 'recipientId');
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
            $table->renameColumn('recipientId', 'userId');
        });
    }
}
