<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedTargetGroup extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_questions', function(Blueprint $table)
		{
			$table->integer('targetRoleId')
				->unsigned()
				->nullable();

			$table->foreign('targetRoleId')
            	->references('id')
            	->on('roles')
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
		Schema::table('survey_questions', function(Blueprint $table)
		{
			$table->dropForeign('survey_questions_targetroleid_foreign');
			$table->dropColumn('targetRoleId');
		});
	}
}
