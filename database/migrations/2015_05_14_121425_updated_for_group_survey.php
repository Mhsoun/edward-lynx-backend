<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdatedForGroupSurvey extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('survey_groups', function(Blueprint $table)
		{
			$table->integer('surveyId')->unsigned();
			$table->foreign('surveyId')
	        	->references('id')
	        	->on('surveys')
	        	->onDelete('cascade');

			$table->integer('groupId')->unsigned();
			$table->foreign('groupId')
	        	->references('id')
	        	->on('Group')
	        	->onDelete('cascade');

			$table->boolean('toEvaluate')->default(false);
		});

		Schema::table('surveys', function(Blueprint $table)
		{
			$table->dropForeign('surveys_groupid_foreign');
			$table->dropColumn('groupId');
		});

		Schema::table('survey_recipients', function(Blueprint $table)
		{
			$table->integer('groupId')->unsigned()->nullable();
			$table->foreign('groupId')
	        	->references('id')
	        	->on('Group')
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
		Schema::dropIfExists('survey_groups');

		Schema::table('surveys', function(Blueprint $table)
		{
			$table->integer('groupId')->unsigned()->nullable();
	        $table->foreign('groupId')
	        	->references('id')
	        	->on('Group')
	        	->onDelete('set null');
		});

		Schema::table('survey_recipients', function(Blueprint $table)
		{
			$table->dropForeign('survey_recipients_groupid_foreign');
			$table->dropColumn('groupId');
		});
	}
}
