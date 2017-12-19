<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RenamnedFields extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('users', function($table)
		{
		    $table->renameColumn('EdwardLynx', 'isAdmin');
			$table->dropColumn([
				// 'defaultInvitationEmailSubject',
				// 'defaultInvitationEmailMessage',
				// 'defaultManualReminderEmailSubject',
				// 'defaultManualReminderEmailMessage',
				// 'defaultToEvaluateEmailSubject',
				// 'defaultToEvaluateEmailMessage'
			]);
		});

		Schema::table('recipients', function($table)
		{
		    $table->renameColumn('owner', 'ownerId');
		});

		Schema::table('group_members', function($table)
		{
		    $table->renameColumn('group', 'groupId');
		    $table->renameColumn('member', 'memberId');
		});

		Schema::table('survey_recipients', function($table)
		{
		    $table->renameColumn('invitedBy', 'invitedById');
		});

		Schema::table('survey_answers', function($table)
		{
		    $table->renameColumn('invitedBy', 'invitedById');
		});

		Schema::rename('survey_invite_recipients', 'survey_candidates');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('users', function($table)
		{
		    $table->renameColumn('isAdmin', 'EdwardLynx');
		});

		Schema::table('recipients', function($table)
		{
		    $table->renameColumn('ownerId', 'owner');
		});

		Schema::table('group_members', function($table)
		{
		    $table->renameColumn('groupId', 'group');
		    $table->renameColumn('memberId', 'member');
		});

		Schema::table('survey_recipients', function($table)
		{
		    $table->renameColumn('invitedById', 'invitedBy');
		});

		Schema::table('survey_answers', function($table)
		{
		    $table->renameColumn('invitedById', 'invitedBy');
		});

		Schema::rename('survey_candidates', 'survey_invite_recipients');
	}
}
