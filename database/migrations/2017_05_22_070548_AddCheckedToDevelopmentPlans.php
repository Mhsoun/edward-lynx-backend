<?php

use App\Models\DevelopmentPlan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCheckedToDevelopmentPlans extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('development_plans', function (Blueprint $table) {
            $table->boolean('checked')->default(false);
        });

        $devPlans = DevelopmentPlan::all();
        foreach ($devPlans as $devPlan) {
            $devPlan->updateChecked();
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('development_plans', function (Blueprint $table) {
            $table->dropColumn('checked');
        });
    }
}
