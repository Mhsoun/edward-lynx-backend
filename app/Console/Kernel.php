<?php namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel {

	/**
	 * The Artisan commands provided by your application.
	 *
	 * @var array
	 */
	protected $commands = [
		'App\Console\Commands\Inspire',
        \App\Console\Commands\SendDueGoalReminders::class
        //doMail create a mailSchedul.php in commands
	];

	/**
	 * Define the application's command schedule.
	 *
	 * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
	 * @return void
	 */
	protected function schedule(Schedule $schedule)
	{
		$schedule->command('inspire')
				 ->hourly();
        //$schedule->command('mailSchedul')->daily();
        
        $schedule->command(Commands\SendDueGoalReminders::class)
        		 ->everyFiveMinutes()
        		 ->withoutOverlapping();
	}

}
