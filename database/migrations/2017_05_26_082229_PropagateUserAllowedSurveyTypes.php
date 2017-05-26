<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PropagateUserAllowedSurveyTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $companies = DB::table('users')
                        ->whereNull('parentId')
                        ->get();
        foreach ($companies as $company) {
            DB::table('users')
                ->where('parentId', $company->id)
                ->update(['allowedSurveyTypes' => $company->allowedSurveyTypes]);
        }

        // All users under the EL company are allowed to create all types of surveys.
        DB::table('users')
            ->where('parentId', 1)
            ->update(['allowedSurveyTypes' => 31]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $users = DB::table('users')
                    ->whereNotNull('parentId')
                    ->update(['allowedSurveyTypes' => 0]);        
    }
}
