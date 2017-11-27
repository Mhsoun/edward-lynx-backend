<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangedGroupTables2 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::rename('survey_groups', 'survey_role_groups');
		Schema::table('survey_role_groups', function($table)
		{
			$table->dropForeign('survey_groups_groupid_foreign');
			$table->dropColumn('groupId');
			$table->integer('roleId')->unsigned();
		});

		Schema::table('surveys', function($table)
		{
			$table->integer('targetGroupId')
				->unsigned()
				->nullable();
			$table->foreign('targetGroupId')
				->references('id')
				->on('groups')
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
		Schema::table('surveys', function($table)
		{
			$table->dropForeign('surveys_targetgroupid_foreign');
			$table->dropColumn('targetGroupId');
		});

		Schema::table('survey_role_groups', function($table)
		{
			$table->dropColumn('roleId');

			$table->integer('groupId')->unsigned();
			$table->foreign('groupId')
				->references('id')
				->on('groups')
				->onDelete('cascade');
		});

		Schema::rename('survey_role_groups', 'survey_groups');
	}
}
