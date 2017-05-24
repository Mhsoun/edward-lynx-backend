<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RemovePolymorphicTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('instant_feedback_recipients')
            ->where('user_type', 'users')
            ->delete();
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->dropColumn('user_type');
        });

        DB::table('survey_answers')
            ->where('answeredByType', 'users')
            ->delete();
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->dropColumn('answeredByType');
        });

        DB::table('survey_candidates')
            ->where('recipientType', 'users')
            ->delete();
        Schema::table('survey_candidates', function (Blueprint $table) {
            $table->dropColumn('recipientType');
        });

        DB::table('survey_recipients')
            ->where('recipientType', 'users')
            ->delete();
        Schema::table('survey_recipients', function (Blueprint $table) {
            $table->dropColumn('recipientType');
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
            $table->string('user_type');
        });
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->string('answeredByType');
        });
        Schema::table('survey_candidates', function (Blueprint $table) {
            $table->string('recipientType');
        });
        Schema::table('survey_recipients', function (Blueprint $table) {
            $table->string('recipientType');
        });
    }
}
