<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevelopmentPlanTeamAttributes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('development_plan_team_attributes', function (Blueprint $table) {
            $table->integer('developmentPlanId')->unsigned();
            $table->integer('position')->unsigned();
            $table->boolean('visible')->default(false);

            $table->foreign('developmentPlanId')
                  ->references('id')->on('development_plans')
                  ->onDelete('cascade');
        });

        Schema::table('development_plans', function (Blueprint $table) {
            $table->dropColumn('team');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('development_plan_team_attributes');

        Schema::table('development_plans', function (Blueprint $table) {
            $table->boolean('team')->default(false);
        });
    }
}
