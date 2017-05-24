<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use App\Models\DevelopmentPlanGoal;
use App\Services\Firebase\FirebaseChannel;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Services\Firebase\FirebaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

class DevelopmentPlanGoalDue extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The Development Plan goal.
     *
     * @var App\Models\DevelopmentPlanGoal
     */
    public $goal;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(DevelopmentPlanGoal $goal)
    {
        $this->goal = $goal;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', FirebaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = "edwardlynx:development-plan-{$this->goal->developmentPlan->id}";
        return (new MailMessage)
                    ->subject(trans('developmentPlan.reminderTitle', [
                        'goal'  => $this->goal->title 
                    ]))
                    ->line(trans('developmentPlan.reminderMessage', [
                        'recipient' => $notifiable->name,
                        'goal'      => $this->goal->title,
                        'due'       => $this->goal->dueDate->diffForHumans()
                    ]))
                    ->action(trans('developmentPlan.reminderAction'), $url);
    }
    
    /**
     * Get the firebase representation of the notification.
     * 
     * @param   mixed   $notifiable
     * @return  App\Services\Firebase\FirebaseNotification
     */
    public function toFirebase($notifiable)
    {
        return (new FirebaseNotification)
            ->title(trans('instantFeedback.requestedTitle', [
                'goal'  => $this->goal->title
            ]))
            ->body(trans('developmentPlan.reminderMessage', [
                'recipient' => $notifiable->name,
                'goal'      => $this->goal->title,
                'due'       => $this->goal->dueDate->diffForHumans()
            ]))
            ->data([
                'type'  => 'dev-plan',
                'id'    => $this->goal->developmentPlan->id
            ])->to($notifiable->deviceTokens());
    }
}
