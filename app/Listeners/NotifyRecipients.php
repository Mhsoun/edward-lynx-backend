<?php

namespace App\Listeners;

use App\Events\SurveyCreated;
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
        // TODO: Notify recipients through firebase
    }
}
