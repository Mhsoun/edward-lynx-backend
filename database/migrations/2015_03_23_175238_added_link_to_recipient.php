<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddedLinkToRecipient extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('survey_recipients', function(Blueprint $table)
        {
            $table->string('link')->unique();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('survey_recipients', function(Blueprint $table)
        {
            $table->dropColumn('link');
        });
    }
}
