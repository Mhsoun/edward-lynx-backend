<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedSubgroup extends Migration {
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('Group', function(Blueprint $table)
        {
            $table->integer('parentGroupId')
                ->unsigned()
                ->nullable();

            $table->foreign('parentGroupId')
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
		Schema::table('Group', function(Blueprint $table)
        {
			$table->dropForeign('groups_parentgroupid_foreign');
            $table->dropColumn('parentGroupId');
        });
	}
}
