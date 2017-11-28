<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeSurveyRecipientsTablePolymorphic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('survey_recipients', function (Blueprint $table) {
            $table->dropForeign('survey_recipients_recipientid_foreign');
            $table->string('recipientType');
        });
        
        DB::table('survey_recipients')
            ->update(['recipientType' => 'recipients']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('survey_recipients')
            ->where('recipientType', 'users')
            ->delete();
        
        Schema::table('survey_recipients', function (Blueprint $table) {
            // $table->foreign('recipientId', 'survey_recipients_recipientid_foreign')
            //       ->references('id')->on('recipients')
            //       ->onDelete('cascade');
            $table->dropColumn('recipientType');
        });
    }
}
