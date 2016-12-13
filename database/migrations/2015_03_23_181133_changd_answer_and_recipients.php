<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangdAnswerAndRecipients extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('survey_recipients', function(Blueprint $table)
        {
            $table->boolean("hasAnswered")->default(false);
        });

        Schema::table('survey_answers', function(Blueprint $table)
        {
            $table->integer('questionId')->unsigned();
            $table->foreign('questionId')
                ->references('questionId')
                ->on('survey_questions')
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
		Schema::table('survey_recipients', function(Blueprint $table)
        {
            $table->dropColumn('hasAnswered');
        });

        Schema::table('survey_answers', function(Blueprint $table)
        {
            $table->dropColumn('questionId');
        });
	}
}
