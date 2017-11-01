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
            $table->integer('survey_id')->unsigned();
            $table->integer('survey_report_file_id')->unsigned();
            $table->integer('recipient_id')->unsigned();

            $table->foreign('survey_id')
                  ->references('id')->on('surveys')
                  ->onDelete('cascade');
            $table->foreign('survey_report_file_id')
                  ->references('id')->on('survey_report_files')
                  ->onDelete('cascade');
            $table->foreign('recipient_id')
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
