<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Models\InstantFeedback;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\InstantFeedbackRequested;

class SendInstantFeedbackRecipientsInvites implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $instantFeedback;

    protected $users = [];

    protected $recipients = [];

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(InstantFeedback $instantFeedback, array $users = [], array $recipients = [])
    {
        $this->instantFeedback = $instantFeedback;
        $this->users = $users;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $notif = new InstantFeedbackRequested($this->instantFeedback);

        foreach ($this->users as $user) {
            $user->notify($notif);
        }

        foreach ($this->recipients as $recipient) {
            $recipient->notify($notif);
        }
    }
}
