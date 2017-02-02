<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCategoryidToDevelopmentPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('development_plans', function (Blueprint $table) {
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
        Schema::table('development_plans', function (Blueprint $table) {
            $table->dropColumn('categoryId');
        });
    }
}
