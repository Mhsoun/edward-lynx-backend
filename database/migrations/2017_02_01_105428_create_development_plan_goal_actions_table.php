<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevelopmentPlanGoalActionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('development_plan_goal_actions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('goalId')->unsigned();
            $table->string('title');
            $table->boolean('checked')->default(false);
            $table->integer('position')->unsigned();
            
            $table->foreign('goalId')
                  ->references('id')->on('development_plan_goals')
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
        Schema::dropIfExists('development_plan_goal_actions');
    }
}
