<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RennamnedFieldsAgain2 extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('survey_extra_answers', function($table)
		{
		    $table->renameColumn('invitedBy', 'invitedById');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('survey_extra_answers', function($table)
		{
		    $table->renameColumn('invitedById', 'invitedBy');
		});
	}
}
