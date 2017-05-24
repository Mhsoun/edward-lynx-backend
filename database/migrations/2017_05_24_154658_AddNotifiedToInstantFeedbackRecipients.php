<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddNotifiedToInstantFeedbackRecipients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->boolean('notified')->default(false);
        });

        DB::table('instant_feedback_recipients')
            ->update(['notified' => true]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->dropColumn('notified');
        });
    }
}
