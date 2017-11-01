<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSurveyCandidateReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('survey_candidate_reports', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('surveyId')->unsigned();
            $table->integer('surveyReportFileId')->unsigned();
            $table->integer('recipientId')->unsigned();

            $table->foreign('surveyId')
                  ->references('id')->on('surveys')
                  ->onDelete('cascade');
            $table->foreign('surveyReportFileId')
                  ->references('id')->on('survey_report_files')
                  ->onDelete('cascade');
            $table->foreign('recipientId')
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
        Schema::dropIfExists('survey_candidate_reports');
    }
}
