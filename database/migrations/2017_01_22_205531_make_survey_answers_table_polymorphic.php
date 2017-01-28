<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeSurveyAnswersTablePolymorphic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('survey_answers', function (Blueprint $table) {
            $table->string('recipientType');
        });
        
        DB::table('survey_answers')
            ->update(['recipientType' => 'recipients']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('survey_candidates')
            ->where('recipientType', 'users')
            ->delete();
        
        Schema::table('survey_candidates', function (Blueprint $table) {
            $table->dropColumn('recipientType');
        });
    }
}
