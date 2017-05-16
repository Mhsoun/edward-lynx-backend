<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\AnonymousUser;
use Illuminate\Bus\Queueable;
use App\Models\InstantFeedback;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Notifications\InstantFeedbackRequested;

class ProcessInstantFeedbackInvites implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    protected $instantFeedback;

    protected $recipients;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(InstantFeedback $instantFeedback, $recipients)
    {
        $this->instantFeedback = $instantFeedback;
        $this->recipients = $recipients;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Retrieve a list of users who have been notified already.
        $notifiedUsers = [];
        foreach ($instantFeedback->users()->where('user_type', 'users') as $user) {
            $notifiedUsers[] = $user->id;
        }

        // Update instant feedback recipients.
        $this->processRecipients($instantFeedback, $recipients);

        // Build a list of recipients that will be notified.
        $newRecipients = [];
        $anonRecipients = [];
        foreach ($recipients as $r) {
            if (!isset($r['id'])) {
                $anonRecipients[] = new AnonymousUser($r['name'], $r['email']);
            } else {
                if (in_array($r['id'], $notifiedUsers)) {
                    continue; // Do not notify already saved users.
                }

                $newRecipients[] = User::find($r['id']);
            }
        }

        $notif = new InstantFeedbackRequested($this->instantFeedback);

        foreach ($newRecipients as $user) {
            $user->notify($notif);
        }

        foreach ($anonRecipients as $recipient) {
            $recipient->notify($notif);
        }
    }
}
