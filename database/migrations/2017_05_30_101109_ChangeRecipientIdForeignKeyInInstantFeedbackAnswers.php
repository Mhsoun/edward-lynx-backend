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
            if ($this->hasForeignKey('instant_feedback_answers', 'instant_feedback_answers_user_id_foreign')) {
                $table->dropForeign('instant_feedback_answers_user_id_foreign');
            } elseif ($this->hasForeignKey('instant_feedback_answers', 'instant_feedback_answers_userid_foreign')) {
                $table->dropForeign('instant_feedback_answers_userid_foreign');
            }
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

    protected function hasForeignKey($table, $key)
    {
        $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys('instant_feedback_answers');
        $foreignKeys = array_map(function ($foreignKey) {
            return $foreignKey->getName();
        }, $foreignKeys);
        return in_array($key, $foreignKeys);
    }
}
