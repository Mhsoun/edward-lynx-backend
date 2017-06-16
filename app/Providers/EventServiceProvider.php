<?php namespace App\Providers;

use Illuminate\Support\Facades\Event;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider {

	/**
	 * The event handler mappings for the application.
	 *
	 * @var array
	 */
	protected $listen = [
		\App\Events\InstantFeedbackKeyExchanged::class => [
			\App\Listeners\MarkInstantFeedbackNotificationRead::class
		],
		\App\Events\SurveyKeyExchanged::class => [
			\App\Listeners\MarkSurveyNotificationRead::class
		]
	];

	/**
	 * Register any other events for your application.
	 *
	 * @return void
	 */
	public function boot()
	{
		parent::boot();

		//
	}

}
