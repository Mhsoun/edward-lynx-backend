<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangedGroupTables extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::rename('Group', 'groups');
		Schema::rename('Recipient_In_Group', 'group_members');
		Schema::rename('Recipient', 'recipients');
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::rename('groups', 'Group');
		Schema::rename('group_members', 'Recipient_In_Group');
		Schema::rename('recipients', 'Recipient');
	}
}
