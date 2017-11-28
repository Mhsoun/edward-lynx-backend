<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemoveInstantFeedbackRecipientUserIdForeignEys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            if ($this->hasForeignKey('instant_feedback_recipients', 'instant_feedback_recipients_user_id_foreign')) {
                $table->dropForeign('instant_feedback_recipients_user_id_foreign');
            }
            if ($this->hasForeignKey('instant_feedback_recipients', 'instant_feedback_recipients_userid_foreign')) {
                $table->dropForeign('instant_feedback_recipients_userid_foreign');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /*
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->foreign('recipientId')
                  ->refereces('id')->on('users')
                  ->onDelete('cascade');
        });
        */
    }

    protected function hasForeignKey($table, $key)
    {
        $foreignKeys = Schema::getConnection()->getDoctrineSchemaManager()->listTableForeignKeys($table);
        $foreignKeys = array_map(function ($foreignKey) {
            return $foreignKey->getName();
        }, $foreignKeys);
        return in_array($key, $foreignKeys);
    }
}
