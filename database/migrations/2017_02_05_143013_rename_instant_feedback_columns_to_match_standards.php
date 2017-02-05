<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameInstantFeedbackColumnsToMatchStandards extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedbacks', function (Blueprint $table) {
            $table->renameColumn('user_id', 'userId');
            $table->renameColumn('closed_at', 'closedAt');
            $table->renameColumn('created_at', 'createdAt');
            $table->renameColumn('updated_at', 'updatedAt');
        });
        
        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->renameColumn('instant_feedback_id', 'instantFeedbackId');
            $table->renameColumn('user_id', 'userId');
            $table->renameColumn('question_id', 'questionId');
        });
        
        Schema::table('instant_feedback_questions', function (Blueprint $table) {
            $table->renameColumn('instant_feedback_id', 'instantFeedbackId');
            $table->renameColumn('question_id', 'questionId');
        });
        
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->renameColumn('instant_feedback_id', 'instantFeedbackId');
            $table->renameColumn('user_id', 'userId');
            $table->renameColumn('answered_at', 'answeredAt');
        });
        
        Schema::table('instant_feedback_shares', function (Blueprint $table) {
            $table->renameColumn('instant_feedback_id', 'instantFeedbackId');
            $table->renameColumn('user_id', 'userId');
            $table->renameColumn('created_at', 'createdAt');
            $table->renameColumn('updated_at', 'updatedAt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instant_feedbacks', function (Blueprint $table) {
            $table->renameColumn('userId', 'user_id');
            $table->renameColumn('closedAt', 'closed_at');
            $table->renameColumn('createdAt', 'created_at');
            $table->renameColumn('updatedAt', 'updated_at');
        });
        
        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->renameColumn('instantFeedbackId', 'instant_feedback_id');
            $table->renameColumn('userId', 'user_id');
            $table->renameColumn('questionId', 'question_id');
        });
        
        Schema::table('instant_feedback_questions', function (Blueprint $table) {
            $table->renameColumn('instantFeedbackId', 'instant_feedback_id');
            $table->renameColumn('questionId', 'question_id');
        });
        
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->renameColumn('instantFeedbackId', 'instant_feedback_id');
            $table->renameColumn('userId', 'user_id');
            $table->renameColumn('answeredAt', 'answered_at');
        });
        
        Schema::table('instant_feedback_shares', function (Blueprint $table) {
            $table->renameColumn('instantFeedbackId', 'instant_feedback_id');
            $table->renameColumn('userId', 'user_id');
            $table->renameColumn('createdAt', 'created_at');
            $table->renameColumn('updatedAt', 'updated_at');
        });
    }
}
