<?php

namespace App\Providers\App\Listeners;

use App\Providers\App\Events\SurveyCreated;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyRecipients
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  SurveyCreated  $event
     * @return void
     */
    public function handle(SurveyCreated $event)
    {
        //
    }
}
