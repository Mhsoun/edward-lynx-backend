<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Forms2 extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('forms', function ($table) {
           // $table->dropColumn('group');
            $table->string('invite_text');
            $table->dateTime('start_date');
            $table->dateTime('end_date');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('forms', function ($table) {
            $table->dropColumn('invite_text');
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
        });
    }

}
