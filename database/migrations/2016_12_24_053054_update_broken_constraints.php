<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Updates foreign key constraints that were pointing to inexistent tables
 * when those tables were renamed.
 */
class UpdateBrokenConstraints extends Migration
{	
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::table('group_members', function(Blueprint $table) {
			$table->dropForeign('recipient_in_group_group_foreign');
			$table->foreign('groupId')->references('id')->on('groups');
			$table->dropForeign('recipient_in_group_member_foreign');
			$table->foreign('memberId')->references('id')->on('recipients');
		});
		
		Schema::table('groups', function(Blueprint $table) {
			$table->dropForeign('group_parentgroupid_foreign');
			$table->foreign('parentGroupId')->references('id')->on('groups')->onDelete('cascade');
		});
		
		Schema::table('survey_answers', function(Blueprint $table) {
			$table->dropForeign('survey_answers_answeredbyid_foreign');
			$table->foreign('answeredById')->references('id')->on('recipients')->onDelete('cascade');
		});
		
		Schema::table('survey_candidates', function(Blueprint $table) {
			$table->dropForeign('survey_invite_recipients_recipientid_foreign');
			$table->foreign('recipientId')->references('id')->on('recipients')->onDelete('cascade');
		});
		
        Schema::table('survey_recipients', function(Blueprint $table) {
            $table->dropForeign('survey_recipients_groupid_foreign');
            $table->foreign('groupId')->references('id')->on('groups');
            $table->dropForeign('survey_recipients_recipientid_foreign');
            $table->foreign('recipientId')->references('id')->on('recipients');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
		Schema::table('group_members', function(Blueprint $table) {
			$table->dropForeign('group_members_groupid_foreign');
			$table->foreign('groupId')->references('id')->on('groups')->onDelete('cascade');
			$table->dropForeign('group_members_memberid_foreign');
			$table->foreign('memberId')->references('id')->on('recipients')->onDelete('cascade');
		});
		
		Schema::table('groups', function(Blueprint $table) {
			$table->dropForeign('groups_parentgroupid_foreign');
			$table->foreign('parentGroupId')->references('id')->on('groups')->onDelete('cascade');
		});
		
		Schema::table('survey_answers', function(Blueprint $table) {
			$table->dropForeign('survey_answers_answeredbyid_foreign');
			$table->foreign('answeredById')->references('id')->on('recipients')->onDelete('cascade');
		});
		
		Schema::table('survey_candidates', function(Blueprint $table) {
			// $table->dropForeign('survey_invite_recipients_recipientid_foreign');
			$table->foreign('recipientId')->references('id')->on('recipients')->onDelete('cascade');
		});
		
        Schema::table('survey_recipients', function(Blueprint $table) {
            $table->dropForeign('survey_recipients_groupid_foreign');
            $table->foreign('groupId')->references('id')->on('groups');
            // $table->dropForeign('survey_recipients_recipientid_foreign');
            $table->foreign('recipientId')->references('id')->on('recipients');
        });
    }
}
