<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
* The constraints for the 'Recipient_In_Group' table is wrong. They should be deleted when the parent group or recipient is deleted.
*/
class FixedInvalidConstraintsRecipientsInGroup extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Recipient_In_Group', function($table)
		{
			$table->dropForeign('recipient_in_group_member_foreign');
			$table->foreign('member')
				->references('id')
				->on('Recipient')
				->onDelete('cascade');

			$table->dropForeign('recipient_in_group_group_foreign');
			$table->foreign('group')
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
		Schema::table('Recipient_In_Group', function($table)
		{
			$table->dropForeign('group_members_memberid_foreign');
			$table->foreign('member')
				->references('id')
				->on('Recipient');

			$table->dropForeign('group_members_groupid_foreign');
			$table->foreign('Group')
				->references('id')
				->on('Recipient');
		});
	}

}
