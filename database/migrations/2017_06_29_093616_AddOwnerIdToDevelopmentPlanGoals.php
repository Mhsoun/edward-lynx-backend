<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddOwnerIdToDevelopmentPlanGoals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('development_plan_goals', function (Blueprint $table) {
            $table->integer('ownerId')->unsigned();
        });

        $goals = DB::table('development_plan_goals')->get();
            foreach ($goals as $goal) {
                $devPlan = DB::table('development_plans')->where('id', $goal->developmentPlanId)->first();
                DB::table('development_plan_goals')->where('id', $goal->id)->update(['ownerId' => $devPlan->ownerId]);
            }

        Schema::table('development_plan_goals', function (Blueprint $table) {
            $table->foreign('ownerId')
                  ->references('id')->on('users')
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
            $table->dropColumn('ownerId');
        });
    }
}
