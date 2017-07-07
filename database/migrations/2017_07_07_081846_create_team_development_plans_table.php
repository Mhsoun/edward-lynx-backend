<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamDevelopmentPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('development_plan_team_attributes');

        Schema::create('team_development_plans', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('ownerId')->unsigned();
            $table->integer('categoryId')->unsigned();
            $table->integer('position')->unsigned()->default(0);
            $table->boolean('visible')->default(false);
            $table->timestamp('createdAt');
            $table->timestamp('updatedAt');

            $table->foreign('ownerId')
                  ->references('id')->on('users')
                  ->onDelete('cascade');
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
        Schema::dropIfExists('team_development_plans');

        Schema::create('development_plan_team_attributes', function (Blueprint $table) {
            $table->integer('developmentPlanId')->unsigned();
            $table->integer('position')->unsigned();
            $table->boolean('visible')->default(false);

            $table->foreign('developmentPlanId')
                  ->references('id')->on('development_plans')
                  ->onDelete('cascade');
        });
    }
}
