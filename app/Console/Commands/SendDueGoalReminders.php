<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Models\DevelopmentPlanGoal;
use App\Notifications\DevelopmentPlanGoalDue;

class SendDueGoalReminders extends Command
{    
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'devplans:remind';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends reminders for development plans goals that are due.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $goals = DevelopmentPlanGoal::due()
                    ->where('reminderSent', false)
                    ->get();
        foreach ($goals as $goal) {
            $goal->developmentPlan->target->notify(new DevelopmentPlanGoalDue($goal));
            $goal->reminderSent = true;
            $goal->save();
        }
    }
}
