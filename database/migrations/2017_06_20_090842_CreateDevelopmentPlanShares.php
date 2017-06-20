<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateDevelopmentPlanShares extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('development_plan_shares', function (Blueprint $table) {
            $table->unsignedInteger('developmentPlanId');
            $table->unsignedInteger('userId');
            $table->primary(['developmentPlanId', 'userId']);

            $table->foreign('developmentPlanId')
                  ->references('id')->on('development_plans')
                  ->onDelete('cascade');
            $table->foreign('userId')
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
        Schema::dropIfExists('development_plan_shares');
    }
}
