<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAnsweredAtToInstantFeedbackRecipientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->dateTimeTz('answered_at')->nullable();
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
            $table->dropColumn('answered_at');
        });
    }
}
