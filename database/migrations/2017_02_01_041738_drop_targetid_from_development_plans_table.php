<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class DropTargetidFromDevelopmentPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('development_plans', function (Blueprint $table) {
            $table->dropForeign(['targetId']);
            $table->dropColumn('targetId');
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
            $table->integer('targetId')->unsigned()->nullable();
            $table->foreign('targetId')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });
    }
}
