<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedParentCategory extends Migration
{
	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('question_categories', function(Blueprint $table)
		{
			$table->integer('parentCategoryId')
            	->unsigned()
            	->nullable();

	        $table->foreign('parentCategoryId')
	            ->references('id')
	            ->on('question_categories')
	            ->onDelete('set null');
		});
	}

	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('question_categories', function(Blueprint $table)
		{
			$table->dropForeign('question_categories_parentcategoryid_foreign');
			$table->dropColumn('parentCategoryId');
		});
	}
}
