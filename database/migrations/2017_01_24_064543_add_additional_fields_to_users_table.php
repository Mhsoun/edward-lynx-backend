<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddAdditionalFieldsToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            //$table->string('role')->nullable();
            $table->string('department');
            $table->string('gender');
            $table->string('city');
            $table->string('country');
            $table->string('position')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // $table->dropColumn('role');
            $table->dropColumn('department');
            $table->dropColumn('gender');
            $table->dropColumn('city');
            $table->dropColumn('country');
            $table->string('position')->nullable(true)->change();
        });
    }
}
