<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevelopmentPlanGoalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('development_plan_goals', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('developmentPlanId')->unsigned();
            $table->string('title');
            $table->text('description');
            $table->boolean('checked')->default(false);
            $table->integer('position')->unsigned();
            $table->dateTimeTz('dueDate')->nullable();
            
            $table->foreign('developmentPlanId')
                  ->references('id')->on('development_plans')
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
        Schema::dropIfExists('development_plan_goals');
    }
}
