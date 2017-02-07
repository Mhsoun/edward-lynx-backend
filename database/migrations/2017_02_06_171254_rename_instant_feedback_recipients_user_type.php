<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenameInstantFeedbackRecipientsUserType extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->renameColumn('userType', 'user_type');
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
            $table->renameColumn('user_type', 'userType');
        });
    }
}
