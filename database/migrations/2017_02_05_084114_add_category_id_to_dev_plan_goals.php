<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCategoryIdToDevPlanGoals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('development_plans', function (Blueprint $table) {
            $table->dropForeign(['categoryId']);
            $table->dropColumn('categoryId');
        });
        
        Schema::table('development_plan_goals', function (Blueprint $table) {
            $table->integer('categoryId')->unsigned()->nullable();
            $table->foreign('categoryId')
                  ->references('id')->on('question_categories')
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
        Schema::table('development_plan_goals', function (Blueprint $table) {
            $table->dropForeign(['categoryId']);
            $table->dropColumn('categoryId');
        });
        
        Schema::table('development_plans', function (Blueprint $table) {
            $table->integer('categoryId')->unsigned()->nullable();
            $table->foreign('categoryId')
                  ->references('id')->on('question_categories')
                  ->onDelete('cascade');
        });
    }
}
