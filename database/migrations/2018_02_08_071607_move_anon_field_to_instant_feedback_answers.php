<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MoveAnonFieldToInstantFeedbackAnswers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $anonRecords = DB::table('instant_feedbacks')
                        ->select('id')
                        ->where('anonymous', true)
                        ->get();

        Schema::table('instant_feedbacks', function (Blueprint $table) {
            $table->dropColumn('anonymous');
        });

        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->boolean('anonymous')->default(false);
        });

        foreach ($anonRecords as $anonRecord) {
            DB::table('instant_feedback_answers')
                ->where('instantFeedbackId', $anonRecord->id)
                ->update(['anonymous' => true]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $anonRecords = DB::table('instant_feedback_answers')
                        ->select('instantFeedbackId')
                        ->where('anonymous', true)
                        ->groupBy('instantFeedbackId')
                        ->get();

        Schema::table('instant_feedback_answers', function (Blueprint $table) {
            $table->dropColumn('anonymous');
        });

        Schema::table('instant_feedbacks', function (Blueprint $table) {
            $table->boolean('anonymous')->default(false);
        });

        foreach ($anonRecords as $anonRecord) {
            DB::table('instant_feedbacks')
                ->where('id', $anonRecord->instantFeedbackId)
                ->update(['anonymous' => true]);
        }
    }
}
