<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MakeInstantFeedbackRecipientsPolymorphic extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            // $table->dropForeign('instant_feedbacks_user_id_foreign');
            $table->string('userType');
        });

        DB::table('instant_feedback_recipients')
            ->update(['userType' => 'users']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('instant_feedback_recipients', function (Blueprint $table) {
            $table->foreign('userId')
                  ->references('id')->on('users')
                  ->onDelete('cascade');

            $table->dropColumn('userType');
        });
    }
}
